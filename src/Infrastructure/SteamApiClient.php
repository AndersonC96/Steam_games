<?php

declare(strict_types=1);

namespace Anderson\SteamGames\Infrastructure;

use Anderson\SteamGames\Exceptions\ApiLimitExceededException;
use Anderson\SteamGames\Exceptions\SteamApiException;
use Anderson\SteamGames\Exceptions\UserNotFoundException;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use GuzzleHttp\Exception\GuzzleException;

final class SteamApiClient
{
    private readonly Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 15,
            'connect_timeout' => 5,
            'http_errors' => false,
        ]);
    }

    public function resolveSteamId(string $username, string $apiKey): string
    {
        if (is_numeric($username) && strlen($username) === 17) {
            return $username;
        }

        $data = $this->getJson('https://api.steampowered.com/ISteamUser/ResolveVanityURL/v1/', [
            'key' => $apiKey,
            'vanityurl' => $username,
        ]);

        if (isset($data['response']['success']) && (int) $data['response']['success'] === 1) {
            return (string) $data['response']['steamid'];
        }

        throw new UserNotFoundException("Usuário '{$username}' não encontrado na base da Steam.");
    }

    public function getPlayerSummaries(string $steamId, string $apiKey): array
    {
        $data = $this->getJson('https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v2/', [
            'key' => $apiKey,
            'steamids' => $steamId,
        ]);

        $player = $data['response']['players'][0] ?? null;
        if (!$player) {
            throw new SteamApiException("Não foi possível carregar os detalhes do perfil.");
        }

        return $player;
    }

    public function getOwnedGames(string $steamId, string $apiKey): array
    {
        $data = $this->getJson('https://api.steampowered.com/IPlayerService/GetOwnedGames/v1/', [
            'key' => $apiKey,
            'steamid' => $steamId,
            'include_appinfo' => true,
        ]);

        $games = $data['response']['games'] ?? null;
        if ($games === null) {
            throw new SteamApiException("A biblioteca deste perfil está privada ou vazia.");
        }

        return $games;
    }

    /**
     * @param int[] $appIds
     */
    public function getMultipleAppDetailsAsync(array $appIds): array
    {
        $promises = [];
        foreach ($appIds as $appId) {
            $promises[$appId] = $this->client->requestAsync('GET', 'https://store.steampowered.com/api/appdetails', [
                'query' => ['appids' => $appId, 'l' => 'brazilian']
            ]);
        }

        return Promise\Utils::unwrap($promises);
    }

    public function getPlayerAchievements(string $steamId, int $appId, string $apiKey): array
    {
        return $this->getJson('https://api.steampowered.com/ISteamUserStats/GetPlayerAchievements/v1/', [
            'key' => $apiKey,
            'steamid' => $steamId,
            'appid' => $appId,
        ]);
    }

    private function getJson(string $url, array $query): array
    {
        try {
            $response = $this->client->request('GET', $url, ['query' => $query]);
            if ($response->getStatusCode() === 429) {
                throw new ApiLimitExceededException("Limite de requisições da Steam atingido. Tente novamente mais tarde.");
            }
            return json_decode((string) $response->getBody(), true) ?? [];
        } catch (GuzzleException $e) {
            throw new SteamApiException("Erro na comunicação com a API da Steam: " . $e->getMessage());
        }
    }
}
