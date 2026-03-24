<?php
    use GuzzleHttp\Client;
    use GuzzleHttp\Exception\GuzzleException;

    function steamClient() {
        return new Client([
            'timeout' => 12,
            'connect_timeout' => 8,
        ]);
    }

    function steamGetJson($url, $query) {
        $client = steamClient();
        $response = $client->request('GET', $url, [
            'query' => $query,
        ]);
        return json_decode($response->getBody(), true);
    }

    function getSteamUserId($username, $apiKey) {
        try {
            $data = steamGetJson('https://api.steampowered.com/ISteamUser/ResolveVanityURL/v1/', [
                'key' => $apiKey,
                'vanityurl' => $username,
            ]);
        } catch (GuzzleException $e) {
            return null;
        }

        if (isset($data['response']['success']) && (int) $data['response']['success'] === 1) {
            return $data['response']['steamid'] ?? null;
        }

        return null;
    }

    function getUserProfile($steamId, $apiKey) {
        try {
            $data = steamGetJson('https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v2/', [
                'key' => $apiKey,
                'steamids' => $steamId,
            ]);
        } catch (GuzzleException $e) {
            return null;
        }

        if (isset($data['response']['players'][0])) {
            $player = $data['response']['players'][0];
            return [
                'username' => $player['personaname'] ?? 'Nome não disponível',
                'avatar' => $player['avatarfull'] ?? 'img/avatar_padrao.png',
                'account_created' => isset($player['timecreated']) ? date('d/m/Y', $player['timecreated']) : 'Data de criação não disponível',
                'country' => $player['loccountrycode'] ?? 'N/A',
            ];
        }

        return null;
    }

    function getSteamUserGames($steamId, $apiKey) {
        try {
            $data = steamGetJson('https://api.steampowered.com/IPlayerService/GetOwnedGames/v1/', [
                'key' => $apiKey,
                'steamid' => $steamId,
                'include_appinfo' => true,
                'include_played_free_games' => false,
            ]);
        } catch (GuzzleException $e) {
            return [];
        }

        return $data['response']['games'] ?? [];
    }

    function getAchievements($steamId, $appId, $apiKey) {
        $client = steamClient();
        try {
            $response = $client->request('GET', "https://api.steampowered.com/ISteamUserStats/GetPlayerAchievements/v1/", [
                'query' => [
                    'key' => $apiKey,
                    'steamid' => $steamId,
                    'appid' => $appId,
                ],
            ]);
            $data = json_decode($response->getBody(), true);
            if (isset($data['playerstats']['achievements'])) {
                $unlocked = count(array_filter($data['playerstats']['achievements'], function ($achievement) {
                    return isset($achievement['achieved']) && (int) $achievement['achieved'] === 1;
                }));
            } else {
                $unlocked = 0;
            }
        } catch (GuzzleException $e) {
            return "Jogo sem conquistas";
        }

        try {
            $response = $client->request('GET', "https://api.steampowered.com/ISteamUserStats/GetSchemaForGame/v2/", [
                'query' => [
                    'key' => $apiKey,
                    'appid' => $appId,
                ],
            ]);
            $data = json_decode($response->getBody(), true);
            $total = isset($data['game']['availableGameStats']['achievements']) ? count($data['game']['availableGameStats']['achievements']) : 0;
            if ($total === 0) {
                return "Jogo sem conquistas";
            }
        } catch (GuzzleException $e) {
            return "Jogo sem conquistas";
        }

        return "{$unlocked}/{$total} Conquistas";
    }

    function formatPlaytime($minutes) {
        if ($minutes <= 0) {
            return "Não jogado";
        }

        if ($minutes === 1) {
            return "1 minuto";
        }

        if ($minutes < 60) {
            return "{$minutes} minutos";
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($remainingMinutes === 1) {
            return "{$hours} horas e 1 minuto";
        }

        return "{$hours} horas e {$remainingMinutes} minutos";
    }

    function getGameDetails($steamId, $appId, $apiKey) {
        try {
            $data = steamGetJson('https://store.steampowered.com/api/appdetails', [
                'appids' => $appId,
            ]);
        } catch (GuzzleException $e) {
            return [
                'price' => 'Preço não disponível',
                'description' => 'Descrição não disponível',
                'image' => 'img/padrao.png',
                'achievements' => 'Jogo sem conquistas',
                'release_date' => 'Data de lançamento não disponível',
            ];
        }

        if (!isset($data[$appId]['data'])) {
            return [
                'price' => 'Preço não disponível',
                'description' => 'Descrição não disponível',
                'image' => 'img/padrao.png',
                'achievements' => 'Jogo sem conquistas',
                'release_date' => 'Data de lançamento não disponível',
            ];
        }
        $gameData = $data[$appId]['data'];
        $achievements = getAchievements($steamId, $appId, $apiKey);
        $image = isset($gameData['header_image']) && $gameData['header_image'] !== null ? $gameData['header_image'] : 'img/padrao.png';
        $releaseDate = isset($gameData['release_date']['date']) ? $gameData['release_date']['date'] : 'Data de lançamento não disponível';

        return [
            'price' => $gameData['price_overview']['final_formatted'] ?? 'Preço não disponível',
            'description' => $gameData['detailed_description'] ?? 'Descrição não disponível',
            'image' => $image,
            'achievements' => $achievements,
            'release_date' => $releaseDate,
        ];
    }

    function getUserGameDetails($username, $apiKey) {
        $steamId = getSteamUserId($username, $apiKey);
        if (!$steamId) {
            return "Usuário não encontrado.";
        }

        $profile = getUserProfile($steamId, $apiKey);
        if (!$profile) {
            return "Perfil do usuário não encontrado.";
        }

        $games = getSteamUserGames($steamId, $apiKey);
        if (empty($games)) {
            return "Nenhum jogo encontrado para este usuário.";
        }

        $result = [
            'profile' => $profile,
            'games' => []
        ];
        foreach ($games as $game) {
            $details = getGameDetails($steamId, $game['appid'], $apiKey);
            $result['games'][] = [
                'nome' => $game['name'],
                'tempo_jogado_minutos' => (int) ($game['playtime_forever'] ?? 0),
                'tempo_jogado' => formatPlaytime($game['playtime_forever']),
                'preco_atual' => $details['price'],
                'descricao' => $details['description'],
                'capa' => $details['image'],
                'conquistas' => $details['achievements'],
                'data_lancamento' => $details['release_date'],
            ];
        }

        return $result;
    }