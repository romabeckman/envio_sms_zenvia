<?php

class SendSmsZenvia {

    const DDI_BR = 55;

    protected $response;
    protected $curl;

    private $CONTA = ''; // informe a conta 
    private $SENHA = ''; // informe a senha 

    private $apiKey; //BASE64 conta|senha
    private $apiUrl = "https://api-rest.zenvia.com/services/send-sms";
    private $remetente;

    const STATUS_CODE_OK = 0;

    public function __construct(string $remetente) {
        $this->remetente = $remetente;
        $this->apiKey = base64_encode($this->CONTA . ':' . $this->SENHA);
    }
    
    protected function exec(string $json): string {
        $curl = curl_init();
        
        curl_setopt($curl, CURLOPT_URL, $this->apiUrl);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_POST, true);

        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "Authorization: Basic {$this->apiKey}",
            "Accept: application/json",
        ));
        
        curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    protected function enviar(string $json): bool {
        $response = $this->exec($json);

        $this->response = json_decode($response);

        if (isset($this->response->sendSmsResponse)) {
            $this->response = $this->response->sendSmsResponse;
            return (bool) ((int) $this->response->statusCode == self::STATUS_CODE_OK);
        } else
            return false;
    }

    public function envioUnico(int $numero, string $mensagem, \DateTime $data): bool {
        $nPara = self::DDI_BR . $numero;

        $sendSmsRequest = array(
            "from" => $this->remetente,
            "to" => $nPara,
            "schedule" => $data->format('Y-m-d'),
            "msg" => $mensagem,
            "callbackOption" => "ALL",
        );

        $json = json_encode(['sendSmsRequest' => $sendSmsRequest]);
        return $this->enviar($json);
    }

    function getResponse() {
        return $this->response;
    }

}
