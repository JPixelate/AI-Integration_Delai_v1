<?php

namespace App\Controllers;
use CodeIgniter\HTTP\ResponseInterface;

class DeepSeekController extends BaseController {
   public function chat() {
      helper('text');
      /** @var \Config\DeepSeek $deepseekConfig */
      $deepseekConfig = config('DeepSeek');
      
      $request = $this->request->getJSON(true);
      $userMessage = esc($request['message'] ?? '');
      $chatHistory = $request['history'] ?? [];
      
      if (!is_array($chatHistory)) {
         $chatHistory = [];
      }
      


      if (empty($userMessage)) {
         return $this->response->setStatusCode(400)->setJSON(['error' => 'Missing message.']);
      }

      $apiKey = getenv('OPENAI_API_KEY');

      if (empty($apiKey)) {
         return $this->response->setStatusCode(500)->setJSON(['error' => 'API configuration missing']);
      }


      $payload = [
         'model' => $deepseekConfig->model,
         'messages' => $chatHistory,
         'temperature' => 0.7
      ];
      

      $client = \Config\Services::curlrequest();
        
         try {
               $response = $client->request('POST', $deepseekConfig->apiUrl, [
                  'headers' => [
                     'Authorization' => 'Bearer ' . $apiKey,
                     'Content-Type' => 'application/json'
                  ],
                  'body' => json_encode($payload)
               ]);

               $body = json_decode($response->getBody(), true);
               $reply = $body['choices'][0]['message']['content'] ?? 'No answer available at the moment. Sorry.';

               return $this->response->setJSON(['reply' => $reply]);
         } 

         catch (\Exception $e) {
               return $this->response->setStatusCode(500)->setJSON([
                  'error' => 'Failed to call DeepSeek API.',
                  'details' => $e->getMessage()
               ]);
         }
    }
}
