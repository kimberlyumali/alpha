<?php
namespace App\Controller\Api;

use App\Controller\Api\AppController;
use Cake\Http\Client;
use Cake\Controller\Component\RequestHandlerComponent;
use Cake\Event\Event;
use Cake\Core\Configure; 
use Cake\Http\ServerRequest;
use Cake\Routing\Router;

class ArtistApiController extends AppController
{
    public function initialize()
    {
        parent::initialize();
        $this->loadComponent('RequestHandler');
    }

    /*
    * Stage 1 Artist Query
    *
    * @param : $artistName (LANY, Prince, Journey)
    * 
    * return  | return_datatype
    * --------------------------
    * code    | int
    * message | Text
    * data    | array
    *  
    * Output @array
    * --------------------------
    * arrays of Artist album(s) sorted by release_date DESC order
    * 
    */
    public function Artist($artistName = null ,$album = null, $albumname = null)
    {

      $http = new Client();
      $curl = curl_init();

      $code = '';
      $message = '';

      curl_setopt_array($curl, [
        CURLOPT_URL => Configure::read('API_ACCESS_TOKEN'), // Spotify Access Token
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => "grant_type=client_credentials",
        CURLOPT_HTTPHEADER => ["Authorization: Basic " . base64_encode(Configure::read('CLIENT_ID').':'.Configure::read('CLIENT_SECRET'))],
      ]);

      $responseToken = curl_exec($curl);
      curl_close($curl);

      $http = new Client(['headers' => ['Authorization' => 'Bearer ' . json_decode($responseToken)->access_token]]);

      $albums = [];
      $tracks = [];
      $sortedReleaseDate = [];
      if(count(explode('/',$this->request->getRequestTarget())) == 3 ) {
         /*
          * Spotify Search Artist endpoint
          * Return : Artist's Albums 
          */
          $getSearchedArtistId = Configure::read('API_SEARCH') . $artistName . '&type=album';
          $responseSearchedArtistId = $http->get($getSearchedArtistId);
          $dataSearchedArtist = $responseSearchedArtistId->getJSON();
        if(isset($dataSearchedArtist['albums']['items'])) {
          foreach($dataSearchedArtist['albums']['items'] as $item) {
            $code = 200;
            $message = "Ok";
              array_push($albums, $item['release_date'] .":". $item['name']);
          } 
        } else {
            $code = 400;
            $message = 'Invalid Artist or empty album';
        }

        rsort($albums);
        foreach($albums as $dataToSort) {
          $albumName = explode(":",$dataToSort);
          array_push($sortedReleaseDate, $albumName[1]);
        }
      } elseif(count(explode('/',$this->request->getRequestTarget())) == 5 && in_array('album',explode('/',$this->request->getRequestTarget()))) {
          // search the artist and album name  
          $getSearchedArtistIdAlbum = Configure::read('API_SEARCH').'album'.'%3A'.$albumname.'%20'.'artist'.'%3A'.$artistName.'&type=album';
          $responseSearchedArtistIdAlbum = $http->get($getSearchedArtistIdAlbum);
          $dataSearchedArtistAlbum = $responseSearchedArtistIdAlbum->getJSON();

          if(isset($dataSearchedArtistAlbum['albums']['items'])) {
            $code = 200;
            $message = 'OK';
            /*
            * Album id from search endpoint pass by Album name
            */
            $AlbumId = $dataSearchedArtistAlbum['albums']['items'][0]['id'];
            /*
            * Spotify Search Album track(s) endpoint
            * Return : Album tracks
            * For this endpoint Tracks are already been sorted by the number of tracks ASC order
            */
            $getSearchedArtistId = Configure::read('API_SEARCH_TRACKS') . $AlbumId . '/tracks';
            $responseSearchedArtistId = $http->get($getSearchedArtistId);
            $dataSearchedArtist = $responseSearchedArtistId->getJSON();
            foreach($dataSearchedArtist['items'] as $track) {
              array_push($tracks, $track['name']);
            }
          } else {
            $code = 400;
            $message = 'invalid enpoint or Empty track(s)';
          }
      } else {
          $code = 400;
          $message = 'Something went wrong. Please contact administrator';
      }

     /*
      * API Response
      */
      $this->set([
        'code'=> $code,
        'message' => $message,
        'data' => (!empty($tracks) ? $tracks : $sortedReleaseDate),
        '_serialize' => ['code','message','artist_name','data'] 
      ]);  

    }
    
}