<?php
App::uses('HttpSocket', 'Network/Http');
/**
 * Mailgun class
 *
 * Enables sending of email over mailgun
 *
 * Licensed under The MIT License
 *
 * @author Brad Koch <bradkoch2007@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class BasicTransport extends AbstractTransport {

/**
 * Configurations
 *
 * @var array
 */
    protected $_config = array();

/**
 * Email header to Mailgun param mapping
 *
 * @var array
 */
    protected $_paramMapping = array(
        'From' => 'from',
        'To' => 'to',
        'Cc' => 'cc',
        'Bcc' => 'bcc',
        'Subject' => 'subject',
        'text' => 'text',
        'html' => 'html',
        'Reply-To' => 'h:Reply-To',
        'Disposition-Notification-To' => 'h:Disposition-Notification-To',
        'Return-Path' => 'h:Return-Path',
        'o:tag' => 'o:tag',
        'mg:tag' => 'o:tag',
        'X-Mailgun-Tag' => 'o:tag',
        'o:campaign' => 'o:campaign',
        'mg:campaign' => 'o:campaign',
        'X-Mailgun-Campaign-Id' => 'o:campaign',
        'o:dkim' => 'o:dkim',
        'mg:dkim' => 'o:dkim',
        'X-Mailgun-Dkim' => 'o:dkim',
        'o:deliverytime' => 'o:deliverytime',
        'mg:deliverytime' => 'o:deliverytime',
        'X-Mailgun-Deliver-By' => 'o:deliverytime',
        'o:testmode' => 'o:testmode',
        'mg:testmode' => 'o:testmode',
        'X-Mailgun-Drop-Message' => 'o:testmode',
        'o:tracking' => 'o:tracking',
        'mg:tracking' => 'o:tracking',
        'X-Mailgun-Track' => 'o:tracking',
        'o:tracking-clicks' => 'o:tracking-clicks',
        'mg:tracking-clicks' => 'o:tracking-clicks',
        'X-Mailgun-Track-Clicks' => 'o:tracking-clicks',
        'o:tracking-opens' => 'o:tracking-opens',
        'mg:tracking-opens' => 'o:tracking-opens',
        'X-Mailgun-Track-Opens' => 'o:tracking-opens',
    );

/**
 * Send mail
 *
 * @params CakeEmail $email
 * @return array
 */
    public function send(CakeEmail $email) {
	$http = new HttpSocket(array(
            'ssl_verify_peer' => false,
            'ssl_verify_host' => false,
            'ssl_allow_self_signed' => true
        ));

        $url = 'https://api.mailgun.net/v2/' . $this->_config['mailgun_domain'] . '/messages';
        $post = array();
        $post_preprocess = array_merge(
            $email->getHeaders(array('from', 'sender', 'replyTo', 'readReceipt', 'returnPath', 'to', 'cc', 'bcc', 'subject')),
            array(
                'text' => $email->message(CakeEmail::MESSAGE_TEXT),
                'html' => $email->message(CakeEmail::MESSAGE_HTML)
            )
        );
        foreach ($post_preprocess as $header => $value) {
            if (!empty($value) && isset($this->_paramMapping[$header])) {
                $key = $this->_paramMapping[$header];
                $post[$key] = $value;
            }
        }
        $request = array(
            'auth' => array(
                'method' => 'Basic',
                'user' => 'api',
                'pass' => $this->_config['api_key']
            )
        );

        $response = $http->post($url, $post, $request);
        if ($response === false) {
            throw new SocketException("Mailgun BasicTransport error, no response", 500);
        }

        $http_status = $response->code;
        if ($http_status != 200) {
            throw new SocketException("Mailgun request failed.  Status: $http_status, Response: {$response->body}", 500);
        }

        return array(
            'headers' => $this->_headersToString($email->getHeaders(), PHP_EOL),
            'message' => implode(PHP_EOL, $email->message())
        );
    }

}
