<?php
namespace App\Controller;

use Cake\Http\Client;
use Cake\Controller\Component\RequestHandlerComponent;
use Cake\Event\Event;
use Cake\Core\Configure; 
use Cake\Http\ServerRequest;

class ArtistController extends AppController
{
    public function initialize()
    {
        parent::initialize();
        $this->loadComponent('RequestHandler');

    }

    /*
    * Stage 1 Artist Query
    */
    public function artists($artistName)
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

      /*
      * Spotify Search Artist endpoint
      * Return : Artist's Albums 
      */
      $getSearchedArtistId = Configure::read('API_SEARCH') . $artistName . '&type=album';
      $responseSearchedArtistId = $http->get($getSearchedArtistId);
      $dataSearchedArtist = $responseSearchedArtistId->getJSON();

      $album = [];
      $sortedReleaseDate = [];

      if(isset($dataSearchedArtist['albums']['items'])) {
        foreach($dataSearchedArtist['albums']['items'] as $item) {
          $code = 200;
          $message = 'Ok';
            array_push($album, $item['release_date'] .":". $item['name']);
        } 
      } else {
          $code = 400;
          $message = 'Invalid Artist or empty album';
      }

      rsort($album);
      foreach($album as $dataToSort) {
        $albumName = explode(":",$dataToSort);
        array_push($sortedReleaseDate, $albumName[1]);
      }

      /*
      * API Response
      */
      $this->set([
        'code'=> $code,
        'message' => $message,
        'data' => $sortedReleaseDate,
        '_serialize' => ['code','message','artist_name','data']
      ]);

    }


    /*
    * Stage 2 Album Query
    */
    public function artistAlbum($artist,$artistname,$album,$albumname) 
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

      // search the artist aldum id first
      $getSearchedArtistIdAlbum = Configure::read('API_SEARCH').'album'.'%3A'. $albumname.'%20'. $artist.'%3A'.$artistname.'&type=album';
      $responseSearchedArtistIdAlbum = $http->get($getSearchedArtistIdAlbum);
      $dataSearchedArtistAlbum = $responseSearchedArtistIdAlbum->getJSON();

      $AlbumId = $dataSearchedArtistAlbum['albums']['items'][0]['id'];

      /*
      * Spotify Search Artist endpoint
      * Return : Album tracks
      * For this endpoint Tracks are already been sorted by the number of tracks ASC order
      */
      $getSearchedArtistId = Configure::read('API_SEARCH_TRACKS') . $AlbumId . '/tracks';
      $responseSearchedArtistId = $http->get($getSearchedArtistId);
      $dataSearchedArtist = $responseSearchedArtistId->getJSON();

      $tracks = [];
        if(isset($dataSearchedArtist['items'])) {
          $code = 200;
          $message = 'OK';
          foreach($dataSearchedArtist['items'] as $track) {
            array_push($tracks, $track['name']);
          }
        } else {
          $code = 400;
          $message = 'invalid id or Empty track(s)';
        }

      /*
      * API Response
      */
      $this->set([
        'code'=> $code,
        'message' => $message,
        'data' => $tracks,
        '_serialize' => ['code','message','data']
      ]);

    }
    
}