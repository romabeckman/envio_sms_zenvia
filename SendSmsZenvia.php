<?php

class SendSmsZenvia {

    const DDI_BR = 55;

    protected $response;
    protected $curl;

    private $CONTA = ''; // informe a conta 
    private $SENHA = '';

    private $apiKey; //BASE64 conta|senha
    private $apiUrl = "https://api-rest.zenvia.com/services/send-sms";
    private $remetente;

    const STATUS_CODE_OK = 0;

    public function __construct(string $remetente) {
        $this->remetente = $remetente;
        $this->apiKey = base64_encode($this->CONTA . ':' . $this->SENHA);
    }

    private function cabecalho() {
        $this->curl = curl_init();
        curl_setopt($this->curl, CURLOPT_URL, $this->apiUrl);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_HEADER, false);
        curl_setopt($this->curl, CURLOPT_POST, true);

        curl_setopt($this->curl, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "Authorization: Basic {$this->apiKey}",
            "Accept: application/json",
        ));
    }

    protected function enviar(string $json): bool {
        $this->cabecalho();
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $json);
        $response = curl_exec($this->curl);
        curl_close($this->curl);

        $this->response = json_decode($response);

        if (isset($this->response->sendSmsResponse)) {
            $this->response = $this->response->sendSmsResponse;
            return (BOOL) ((INT) $this->response->statusCode == Zenvia::STATUS_CODE_OK);
        } else
            return FALSE;
    }

    public function envioUnico(int $numero, string $mensagem, \DateTime $data): bool {
        $nPara = Zenvia::DDI_BR . $numero;

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
