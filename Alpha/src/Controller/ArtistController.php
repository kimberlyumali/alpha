<?php
namespace App\Controller;

use Cake\Http\Client;

class ArtistController extends AppController
{
    public function initialize()
    {
      
        parent::initialize();
        $this->loadComponent('RequestHandler');

    }

    /*
    * Stage 1 Artist Query
    * 
    * STILL IN PROGRESS
    * 
    * @params: $artistId
    */
    public function Albums($artistId = "")
    {

      $accessToken = 'BQAoQo8XF_fPyt_Ha9LGXFfwcLSWNtgd31F2mj8UEqE-h5PUqvQyWoVIpKv6GP7_jWF7FdVSboMTmmJYh04IAfDWMhQzH2zqc_WQce1K8nVFjGGPd8pYgrlHqEkYZ2vu2zwBeHCWx4rzUqhw48nJBi1OLr_8q_dyQlnbv7WXrH9VtEQ';

      $http = new Client(['headers' => ['Authorization' => 'Bearer ' . $accessToken]]);
        
      // Artist's id
      $artist_id = '49tQo2QULno7gxHutgccqF';

      // Spotify Endpoint
      $playlist_url = 'https://api.spotify.com/v1/artists/'. $artist_id .'/albums';

      $data = [];
      $response = $http->get($playlist_url);
      $data = $response->getJson();

      foreach($data['items'] as $item) {
        array_push($data, $item['name']);
      }


      $this->set([
          'code' => 200,
          'message' => 'Success',
          'data' => $data,
          '_serialize' => ['data'],
      ]);
    }
}