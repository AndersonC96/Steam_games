<?php
    use GuzzleHttp\Client;
    function getSteamUserId($username, $apiKey){
        $client = new Client();
        $response = $client->request('GET', 'https://api.steampowered.com/ISteamUser/ResolveVanityURL/v1/', [
            'query' => [
                'key' => $apiKey,
                'vanityurl' => $username,
            ],
        ]);
        $data = json_decode($response->getBody(), true);
        if($data['response']['success'] == 1){
            return $data['response']['steamid'];
        }
        return null;
    }
    function getSteamUserGames($steamId, $apiKey){
        $client = new Client();
        $response = $client->request('GET', 'https://api.steampowered.com/IPlayerService/GetOwnedGames/v1/', [
            'query' => [
                'key' => $apiKey,
                'steamid' => $steamId,
                'include_appinfo' => true,
                'include_played_free_games' => false,
            ],
        ]);
        return json_decode($response->getBody(), true)['response']['games'] ?? [];
    }
    function getGamePrice($appId){
        $client = new Client();
        $response = $client->request('GET', "https://store.steampowered.com/api/appdetails", [
            'query' => [
                'appids' => $appId,
            ],
        ]);
        $data = json_decode($response->getBody(), true);
        return $data[$appId]['data']['price_overview']['final_formatted'] ?? 'Preço não disponível';
    }
    function getUserGameDetails($username, $apiKey){
        $steamId = getSteamUserId($username, $apiKey);
        if(!$steamId){
            return "Usuário não encontrado.";
        }
        $games = getSteamUserGames($steamId, $apiKey);
        if(empty($games)){
            return "Nenhum jogo encontrado para este usuário.";
        }
        $result = [];
        foreach($games as $game){
            $price = getGamePrice($game['appid']);
            $result[] = [
                'nome' => $game['name'],
                'tempo_jogado' => round($game['playtime_forever'] / 60, 2) . ' horas',
                'preco_atual' => $price,
            ];
        }
        return $result;
    }