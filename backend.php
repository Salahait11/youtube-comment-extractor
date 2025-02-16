<?php
// backend.php (Ce fichier gérera les requêtes AJAX et interagira avec l'API YouTube)

// Remplacez par votre clé API réelle (stockez-la en toute sécurité, pas directement dans le code)
$apiKey = "AIzaSyC6T-kCIWBVeYScw6PbfcC9kW0WLCxcpS0"; 

// Vérifier la méthode de la requête
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST["action"] ?? "";

    switch ($action) {
        case "extractComments":
            extractComments($apiKey);
            break;
        case "getVideoInfo":
            getVideoInfo($apiKey);
            break;
        case "searchVideos":
            searchVideos($apiKey);
            break;
        case "channelVideos":
            channelVideos($apiKey);
            break;
        case "channelStats":
            channelStats($apiKey);
            break;
        case "channelPlaylists":
            channelPlaylists($apiKey);
            break;
        case "playlistVideos":
            playlistVideos($apiKey);
            break;
        case "getChannelId":
            getChannelId($apiKey);
            break;
        default:
            http_response_code(400); // Bad Request
            echo json_encode(["error" => "Action invalide."]);
            break;
    }
} else {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["error" => "Méthode non autorisée. Utilisez POST."]);
}

function extractComments($apiKey) {
    $videoId = $_POST["videoId"] ?? "";
    $includeUsername = $_POST["includeUsername"] ?? false;
    $includeTimestamp = $_POST["includeTimestamp"] ?? false;

    if (empty($videoId)) {
        http_response_code(400);
        echo json_encode(["error" => "Video ID manquant."]);
        return;
    }

    $allComments = [];
    $nextPageToken = '';

    try {
        while (true) {
            $url = "https://www.googleapis.com/youtube/v3/commentThreads?part=snippet&videoId=" . urlencode($videoId) . "&maxResults=100&key=" . urlencode($apiKey);

            if ($nextPageToken) {
                $url .= "&pageToken=" . urlencode($nextPageToken);
            }

            $response = file_get_contents($url); // Utiliser file_get_contents pour une requête simple
            if ($response === false) {
                throw new Exception("Erreur lors de la requête API: " . error_get_last()['message']);
            }
            $data = json_decode($response, true);

            if (!isset($data['items']) || !is_array($data['items'])) {
                break;
            }

            foreach ($data['items'] as $item) {
                $snippet = $item['snippet']['topLevelComment']['snippet'];
                $commentText = $snippet['textDisplay'];
                $authorName = $snippet['authorDisplayName'];
                $publishedAt = $snippet['publishedAt'];

                $commentString = $commentText;
                if ($includeUsername) {
                    $commentString = $authorName . ": " . $commentString;
                }
                if ($includeTimestamp) {
                    $date = new DateTime($publishedAt);
                    $formattedDate = $date->format('Y-m-d H:i:s');
                    $commentString = "[" . $formattedDate . "] " . $commentString;
                }
                $allComments[] = $commentString;
            }

            if (!isset($data['nextPageToken'])) {
                break;
            }
            $nextPageToken = $data['nextPageToken'];
        }

        echo json_encode(["comments" => $allComments]);

    } catch (Exception $e) {
        http_response_code(500); // Internal Server Error
        echo json_encode(["error" => "Erreur lors de l'extraction des commentaires: " . $e->getMessage()]);
    }
}


function getVideoInfo($apiKey) {
    $videoId = $_POST["videoId"] ?? "";

    if (empty($videoId)) {
        http_response_code(400);
        echo json_encode(["error" => "Video ID manquant."]);
        return;
    }

    $url = "https://www.googleapis.com/youtube/v3/videos?part=snippet,statistics&id=" . urlencode($videoId) . "&key=" . urlencode($apiKey);

    try {
        $response = file_get_contents($url);
        if ($response === false) {
            throw new Exception("Erreur lors de la requête API: " . error_get_last()['message']);
        }
        $data = json_decode($response, true);

        if (isset($data['items']) && is_array($data['items']) && count($data['items']) > 0) {
            echo json_encode($data['items'][0]);
        } else {
            echo json_encode(["error" => "Aucune information sur la vidéo trouvée."]);
        }

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => "Erreur lors de la récupération des informations de la vidéo: " . $e->getMessage()]);
    }
}


function searchVideos($apiKey) {
    $keyword = $_POST["keyword"] ?? "";

    if (empty($keyword)) {
        http_response_code(400);
        echo json_encode(["error" => "Mot-clé manquant."]);
        return;
    }

    $url = "https://www.googleapis.com/youtube/v3/search?part=snippet&q=" . urlencode($keyword) . "&maxResults=10&type=video&key=" . urlencode($apiKey);

    try {
        $response = file_get_contents($url);
        if ($response === false) {
            throw new Exception("Erreur lors de la requête API: " . error_get_last()['message']);
        }
        $data = json_decode($response, true);

        echo json_encode($data['items'] ?? []); // Retourner un tableau vide si aucun résultat

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => "Erreur lors de la recherche de vidéos: " . $e->getMessage()]);
    }
}

function channelVideos($apiKey) {
    $channelId = $_POST["channelId"] ?? "";

    if (empty($channelId)) {
        http_response_code(400);
        echo json_encode(["error" => "Channel ID manquant."]);
        return;
    }

    $url = "https://www.googleapis.com/youtube/v3/search?part=snippet&channelId=" . urlencode($channelId) . "&maxResults=10&order=date&type=video&key=" . urlencode($apiKey);

    try {
        $response = file_get_contents($url);
        if ($response === false) {
            throw new Exception("Erreur lors de la requête API: " . error_get_last()['message']);
        }
        $data = json_decode($response, true);

        echo json_encode($data['items'] ?? []);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => "Erreur lors de la récupération des vidéos de la chaîne: " . $e->getMessage()]);
    }
}

function channelStats($apiKey) {
    $channelId = $_POST["channelId"] ?? "";

    if (empty($channelId)) {
        http_response_code(400);
        echo json_encode(["error" => "Channel ID manquant."]);
        return;
    }

    $url = "https://www.googleapis.com/youtube/v3/channels?part=statistics&id=" . urlencode($channelId) . "&key=" . urlencode($apiKey);

    try {
        $response = file_get_contents($url);
        if ($response === false) {
            throw new Exception("Erreur lors de la requête API: " . error_get_last()['message']);
        }
        $data = json_decode($response, true);

        if (isset($data['items']) && is_array($data['items']) && count($data['items']) > 0) {
            echo json_encode($data['items'][0]['statistics']);
        } else {
            echo json_encode(["error" => "Aucune statistique de chaîne trouvée."]);
        }

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => "Erreur lors de la récupération des statistiques de la chaîne: " . $e->getMessage()]);
    }
}


function channelPlaylists($apiKey) {
    $channelId = $_POST["channelId"] ?? "";

    if (empty($channelId)) {
        http_response_code(400);
        echo json_encode(["error" => "Channel ID manquant."]);
        return;
    }

    $url = "https://www.googleapis.com/youtube/v3/playlists?part=snippet&channelId=" . urlencode($channelId) . "&maxResults=10&key=" . urlencode($apiKey);

    try {
        $response = file_get_contents($url);
        if ($response === false) {
            throw new Exception("Erreur lors de la requête API: " . error_get_last()['message']);
        }
        $data = json_decode($response, true);

        echo json_encode($data['items'] ?? []);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => "Erreur lors de la récupération des playlists de la chaîne: " . $e->getMessage()]);
    }
}


function playlistVideos($apiKey) {
    $playlistId = $_POST["playlistId"] ?? "";

    if (empty($playlistId)) {
        http_response_code(400);
        echo json_encode(["error" => "Playlist ID manquant."]);
        return;
    }

    $url = "https://www.googleapis.com/youtube/v3/playlistItems?part=snippet&playlistId=" . urlencode($playlistId) . "&maxResults=10&key=" . urlencode($apiKey);

    try {
        $response = file_get_contents($url);
        if ($response === false) {
            throw new Exception("Erreur lors de la requête API: " . error_get_last()['message']);
        }
        $data = json_decode($response, true);

        echo json_encode($data['items'] ?? []);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => "Erreur lors de la récupération des vidéos de la playlist: " . $e->getMessage()]);
    }
}


function getChannelId($apiKey) {
    $identifier = $_POST["identifier"] ?? "";

    if (empty($identifier)) {
        http_response_code(400);
        echo json_encode(["error" => "Identifiant manquant."]);
        return;
    }

    if (strpos($identifier, "https://www.youtube.com/channel/") === 0) {
        $parts = explode("/", $identifier);
        echo json_encode(["channelId" => $parts[4]]);
        return;
    }

    $url = "https://www.googleapis.com/youtube/v3/search?part=snippet&q=" . urlencode($identifier) . "&type=channel&key=" . urlencode($apiKey);

    try {
        $response = file_get_contents($url);
        if ($response === false) {
            throw new Exception("Erreur lors de la requête API: " . error_get_last()['message']);
        }
        $data = json_decode($response, true);

        if (isset($data['items']) && is_array($data['items']) && count($data['items']) > 0) {
            echo json_encode(["channelId" => $data['items'][0]['id']['channelId']]);
        } else {
            echo json_encode(["error" => "Aucune chaîne trouvée avec cet identifiant."]);
        }

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => "Erreur lors de la recherche de l'ID de la chaîne: " . $e->getMessage()]);
    }
}

?>