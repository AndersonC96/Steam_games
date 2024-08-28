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
    function getAchievements($steamId, $appId, $apiKey){
        $client = new Client();
        try{
            $response = $client->request('GET', "https://api.steampowered.com/ISteamUserStats/GetPlayerAchievements/v1/", [
                'query' => [
                    'key' => $apiKey,
                    'steamid' => $steamId,
                    'appid' => $appId,
                ],
            ]);
            $data = json_decode($response->getBody(), true);
            if(isset($data['playerstats']['achievements'])){
                $unlocked = count(array_filter($data['playerstats']['achievements'], function ($achievement){
                    return $achievement['achieved'] == 1;
                }));
            }else{
                $unlocked = 0;
            }
        }catch(GuzzleHttp\Exception\ClientException $e){
            return "0/0 Conquistas";
        }
        try{
            $response = $client->request('GET', "https://api.steampowered.com/ISteamUserStats/GetSchemaForGame/v2/", [
                'query' => [
                    'key' => $apiKey,
                    'appid' => $appId,
                ],
            ]);
            $data = json_decode($response->getBody(), true);
            $total = isset($data['game']['availableGameStats']['achievements']) ? count($data['game']['availableGameStats']['achievements']) : 0;
        }catch(GuzzleHttp\Exception\ClientException $e){
            $total = 0;
        }
        return "{$unlocked}/{$total} Conquistas";
    }
    function formatPlaytime($minutes){
        if($minutes === 0){
            return "Não jogado";
        }elseif($minutes < 60){
            return "{$minutes} minutos";
        }else{
            $hours = floor($minutes / 60);
            $remainingMinutes = $minutes % 60;
            return "{$hours} horas e {$remainingMinutes} minutos";
        }
    }
    function getGameDetails($steamId, $appId, $apiKey){
        $client = new Client();
        $response = $client->request('GET', "https://store.steampowered.com/api/appdetails", [
            'query' => [
                'appids' => $appId,
            ],
        ]);
        $data = json_decode($response->getBody(), true);
        if(!isset($data[$appId]['data'])){
            return [
                'price' => 'Preço não disponível',
                'description' => 'Descrição não disponível',
                'image' => 'img/padrao.jpg',
                'achievements' => 'Conquistas não disponíveis',
            ];
        }
        $gameData = $data[$appId]['data'];
        $achievements = getAchievements($steamId, $appId, $apiKey);
        $image = $gameData['header_image'] ?? 'img/padrao.jpg';
        return [
            'price' => $gameData['price_overview']['final_formatted'] ?? 'Preço não disponível',
            'description' => $gameData['short_description'] ?? 'Descrição não disponível',
            'image' => $image,
            'achievements' => $achievements,
        ];
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
            $details = getGameDetails($steamId, $game['appid'], $apiKey);
            $result[] = [
                'nome' => $game['name'],
                'tempo_jogado' => formatPlaytime($game['playtime_forever']),
                'preco_atual' => $details['price'],
                'descricao' => $details['description'],
                'capa' => $details['image'],
                'conquistas' => $details['achievements'],
            ];
        }
        return $result;
    }