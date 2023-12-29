<?php

/**
 * Class SendSmsZenvia
 *
 * A class for sending SMS using the Zenvia API.
 */
class SendSmsZenvia {

    /**
     * Country code for Brazil.
     */
    const DDI_BR = 55;

    /**
     * Zenvia API base URL.
     */
    private string $apiUrl = "https://api-rest.zenvia.com/services/send-sms";

    /**
     * Zenvia account.
     * @var string
     */
    private string $account;

    /**
     * Zenvia password.
     * @var string
     */
    private string $password;

    /**
     * Zenvia API key (BASE64 encoded account and password).
     * @var string
     */
    private string $apiKey;

    /**
     * SMS sender.
     * @var string
     */
    private string $sender;

    /**
     * Response from the API.
     * @var object|null
     */
    protected ?object $response = null;

    /**
     * HTTP client.
     * @var resource|null
     */
    private ?resource $curl = null;

    /**
     * Status code for successful API response.
     */
    const STATUS_CODE_OK = 0;

    /**
     * SendSmsZenvia constructor.
     *
     * @param string $sender SMS sender.
     */
    public function __construct(string $sender) {
        $this->sender = $sender;
        $this->account = getenv('ZENVIA_ACCOUNT'); // Update with your .env variable
        $this->password = getenv('ZENVIA_PASSWORD'); // Update with your .env variable
        $this->apiKey = base64_encode($this->account . ':' . $this->password);
    }

    /**
     * Execute the HTTP request.
     *
     * @param string $json JSON data to send.
     * @return string API response.
     */
    protected function execute(string $json): string {
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

        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $json);
        $response = curl_exec($this->curl);

        return $response;
    }

    /**
     * Send SMS using the Zenvia API.
     *
     * @param string $json JSON data to send.
     * @return bool True if the SMS was sent successfully, false otherwise.
     */
    protected function send(string $json): bool {
        $response = $this->execute($json);

        $this->response = json_decode($response);

        if (isset($this->response->sendSmsResponse)) {
            $this->response = $this->response->sendSmsResponse;
            return (bool)((int)$this->response->statusCode == self::STATUS_CODE_OK);
        } else {
            return false;
        }
    }

    /**
     * Send a single SMS.
     *
     * @param int $number Recipient's phone number.
     * @param string $message SMS message.
     * @param \DateTime $date Scheduled date for sending the SMS.
     * @return bool True if the SMS was sent successfully, false otherwise.
     */
    public function sendSingle(int $number, string $message, \DateTime $date): bool {
        $toNumber = self::DDI_BR . $number;

        $sendSmsRequest = array(
            "from" => $this->sender,
            "to" => $toNumber,
            "schedule" => $date->format('Y-m-d'),
            "msg" => $message,
            "callbackOption" => "ALL",
        );

        $json = json_encode(['sendSmsRequest' => $sendSmsRequest]);
        return $this->send($json);
    }

    /**
     * Get the API response.
     *
     * @return object|null API response.
     */
    public function getResponse(): ?object {
        return $this->response;
    }

    /**
     * Destructor to close the cURL resource.
     */
    public function __destruct() {
        if ($this->curl) {
            curl_close($this->curl);
        }
    }
}
