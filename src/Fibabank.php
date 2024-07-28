<?php
/*
Class Fibabanka
@author Selim Hallaç
@blog selimhallac.com
*/
namespace Phpdev;

class Fibabank
{
    public $corporationCode = "";
    public $accountNo = "";
    public $password = "";
    
    function __construct($corporationCode, $accountNo, $password)
    {
        $this->corporationCode = $corporationCode;
        $this->accountNo = $accountNo;
        $this->password = $password;
    }
    
    private function formatDate($date)
    {
        return str_replace('-', '', $date);
    }

    public function hesap_hareketleri($startDate, $endDate)
    {
        try {
            $formattedStartDate = $this->formatDate($startDate);
            $formattedEndDate = $this->formatDate($endDate);

            $xml = <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:fib="http://fibabanka.com/">
   <soapenv:Header/>
   <soapenv:Body>
      <fib:GetStatementInfoRequest>
         <fib:corporationCode>{$this->corporationCode}</fib:corporationCode>
         <fib:accountNo>{$this->accountNo}</fib:accountNo>
         <fib:startDate>{$formattedStartDate}</fib:startDate>
         <fib:endDate>{$formattedEndDate}</fib:endDate>
         <fib:password>{$this->password}</fib:password>
         <fib:summary>0</fib:summary>
      </fib:GetStatementInfoRequest>
   </soapenv:Body>
</soapenv:Envelope>
XML;

            $headers = array(
                "Content-Type: text/xml; charset=UTF-8",
                "Content-Length: " . strlen($xml),
                "SOAPAction: \"http://fibabanka.com/GetStatementInfo\"",
            );

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://apis.fibabanka.com.tr/services/StatementService.svc",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => $xml,
                CURLOPT_HTTPHEADER => $headers,
            ));
        
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
        
            if ($err) {
                $res['statu'] = false;
                $res['response'] = 'cURL error: ' . $err;
            } else {
                $response = str_replace("<SOAP-ENV:Envelope xmlns:SOAP-ENV='http://schemas.xmlsoap.org/soap/envelope/' xmlns:fib='http://fibabanka.com/'>", '', $response);
                $response = str_replace("<SOAP-ENV:Header/><SOAP-ENV:Body>", '', $response);
                $response = str_replace("</SOAP-ENV:Body></SOAP-ENV:Envelope>", '', $response);
                $responseXml = simplexml_load_string($response);
                if (isset($responseXml->branchInfo)) {
                    $responseArray = ($responseXml);
                    $res['statu'] = true;
                    $res['response'] = $responseArray;
                } else {
                    $res['statu'] = false;
                    $res['response'] = $response;
                }
            }
            return json_encode($res);
        
        } catch (Throwable $e) {
            $res['statu'] = false;
            $res['response'] = 'Bağlantı problemi oluştu.';
            return json_encode($res);
        }
    }
}
?>