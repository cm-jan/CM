<?php

class CM_Mail extends CM_View_Abstract implements CM_Typed {

    /** @var CM_Model_User|null */
    private $_recipient;

    /** @var CM_Site_Abstract */
    private $_site;

    /** @var array */
    private $_to = array();

    /** @var array */
    private $_replyTo = array();

    /** @var array */
    private $_cc = array();

    /** @var array */
    private $_bcc = array();

    /** @var array */
    private $_sender;

    /** @var string|null */
    private $_subject = null;

    /** @var string|null */
    private $_textBody = null;

    /** @var string|null */
    private $_htmlBody = null;

    /** @var boolean */
    private $_verificationRequired = true;

    /** @var boolean */
    private $_renderLayout = false;

    /** @var array */
    protected $_tplParams = array();

    /**
     * @param CM_Model_User|string|null $recipient
     * @param array|null                $tplParams
     * @param CM_Site_Abstract|null     $site
     * @throws CM_Exception_Invalid
     */
    public function __construct($recipient = null, array $tplParams = null, CM_Site_Abstract $site = null) {
        if ($this->hasTemplate()) {
            $this->setRenderLayout(true);
        }
        if ($tplParams) {
            foreach ($tplParams as $key => $value) {
                $this->setTplParam($key, $value);
            }
        }
        if (!is_null($recipient)) {
            if (is_string($recipient)) {
                $this->addTo($recipient);
            } elseif ($recipient instanceof CM_Model_User) {
                $this->_recipient = $recipient;
                $this->addTo($this->_recipient->getEmail());
                $this->setTplParam('recipient', $recipient);
            } else {
                throw new CM_Exception_Invalid('Invalid Recipient defined.');
            }
        }

        if (!$site && $this->_recipient) {
            $site = $this->_recipient->getSite();
        }
        if (!$site) {
            $site = CM_Site_Abstract::factory();
        }
        $this->_site = $site;

        $this->setTplParam('siteName', $this->_site->getName());
        $this->setSender($this->_site->getEmailAddress(), $this->_site->getName());
    }

    /**
     * @param string $key
     * @param mixed  $value
     * @return CM_Component_Abstract
     */
    public function setTplParam($key, $value) {
        $this->_tplParams[$key] = $value;
        return $this;
    }

    /**
     * @return array
     */
    public function getTplParams() {
        return $this->_tplParams;
    }

    /**
     * @return CM_Model_User|null
     */
    public function getRecipient() {
        return $this->_recipient;
    }

    /**
     * @param string      $address
     * @param string|null $name
     */
    public function addTo($address, $name = null) {
        $address = (string) $address;
        $name = is_null($name) ? $name : (string) $name;
        $this->_to[] = array('address' => $address, 'name' => $name);
    }

    /**
     * @return array
     */
    public function getTo() {
        return $this->_to;
    }

    /**
     * @param string      $address
     * @param string|null $name
     */
    public function addReplyTo($address, $name = null) {
        $address = (string) $address;
        $name = is_null($name) ? $name : (string) $name;
        $this->_replyTo[] = array('address' => $address, 'name' => $name);
    }

    /**
     * @return array
     */
    public function getReplyTo() {
        return $this->_replyTo;
    }

    /**
     * @param string      $address
     * @param string|null $name
     */
    public function addCc($address, $name = null) {
        $address = (string) $address;
        $name = is_null($name) ? $name : (string) $name;
        $this->_cc[] = array('address' => $address, 'name' => $name);
    }

    /**
     * @return array
     */
    public function getCc() {
        return $this->_cc;
    }

    /**
     * @param string      $address
     * @param string|null $name
     */
    public function addBcc($address, $name = null) {
        $address = (string) $address;
        $name = is_null($name) ? $name : (string) $name;
        $this->_bcc[] = array('address' => $address, 'name' => $name);
    }

    /**
     * @return array
     */
    public function getBcc() {
        return $this->_bcc;
    }

    /**
     * @return string|null
     */
    public function getHtml() {
        return $this->_htmlBody;
    }

    /**
     * @param string $html
     */
    public function setHtml($html) {
        $this->_htmlBody = $html;
    }

    /**
     * @return array
     */
    public function getSender() {
        return $this->_sender;
    }

    /**
     * @param string      $address
     * @param string|null $name
     */
    public function setSender($address, $name = null) {
        $address = (string) $address;
        $name = is_null($name) ? $name : (string) $name;
        $this->_sender = array('address' => $address, 'name' => $name);
    }

    /**
     * @return string|null
     */
    public function getSubject() {
        return $this->_subject;
    }

    /**
     * @param string $subject
     */
    public function setSubject($subject) {
        $this->_subject = $subject;
    }

    /**
     * @return string|null
     */
    public function getText() {
        return $this->_textBody;
    }

    /**
     * @param string $text
     */
    public function setText($text) {
        $this->_textBody = $text;
    }

    /**
     * @param boolean $state OPTIONAL
     */
    public function setVerificationRequired($state = true) {
        $this->_verificationRequired = $state;
    }

    /**
     * @return boolean
     */
    public function getRenderLayout() {
        return $this->_renderLayout;
    }

    /**
     * @param boolean $state OPTIONAL
     */
    public function setRenderLayout($state = true) {
        $this->_renderLayout = (boolean) $state;
    }

    /**
     * @return boolean
     */
    public function hasTemplate() {
        return is_subclass_of($this, 'CM_Mail');
    }

    /**
     * @return array array($subject, $html, $text)
     */
    public function render() {
        $language = null;
        if ($this->_recipient) {
            $language = $this->_recipient->getLanguage();
        }
        return $this->_render($language);
    }

    /**
     * @param boolean|null $delayed
     * @throws CM_Exception_Invalid
     */
    public function send($delayed = null) {
        $delayed = (boolean) $delayed;
        if (empty($this->_to)) {
            throw new CM_Exception_Invalid('No recipient specified.');
        }
        $verificationMissing = $this->_verificationRequired && $this->_recipient && !$this->_recipient->getEmailVerified();
        if ($verificationMissing) {
            return;
        }

        list($subject, $html, $text) = $this->render();

        if ($delayed) {
            $this->_queue($subject, $text, $html);
        } else {
            $this->_send($subject, $text, $html);
        }
    }

    public function sendDelayed() {
        $this->send(true);
    }

    /**
     * @return int
     */
    public static function getQueueSize() {
        return CM_Db_Db::count('cm_mail');
    }

    /**
     * @param int $limit
     */
    public static function processQueue($limit) {
        $limit = (int) $limit;
        $result = CM_Db_Db::execRead('SELECT * FROM `cm_mail` ORDER BY `createStamp` LIMIT ' . $limit);
        while ($row = $result->fetch()) {
            $mail = new CM_Mail();
            foreach (unserialize($row['to']) as $to) {
                $mail->addTo($to['address'], $to['name']);
            }
            foreach (unserialize($row['replyTo']) as $replyTo) {
                $mail->addReplyTo($replyTo['address'], $replyTo['name']);
            }
            foreach (unserialize($row['cc']) as $cc) {
                $mail->addCc($cc['address'], $cc['name']);
            }
            foreach (unserialize($row['bcc']) as $bcc) {
                $mail->addBcc($bcc['address'], $bcc['name']);
            }
            $sender = unserialize($row['sender']);
            $mail->setSender($sender['address'], $sender['name']);
            $mail->_send($row['subject'], $row['text'], $row['html']);
            CM_Db_Db::delete('cm_mail', array('id' => $row['id']));
        }
    }

    private function _queue($subject, $text, $html) {
        CM_Db_Db::insert('cm_mail', array(
            'subject'     => $subject,
            'text'        => $text,
            'html'        => $html,
            'createStamp' => time(),
            'sender'      => serialize($this->getSender()),
            'replyTo'     => serialize($this->getReplyTo()),
            'to'          => serialize($this->getTo()),
            'cc'          => serialize($this->getCc()),
            'bcc'         => serialize($this->getBcc()),
        ));
    }

    private function _log($subject, $text) {
        $msg = '* ' . $subject . ' *' . PHP_EOL . PHP_EOL;
        $msg .= $text . PHP_EOL;
        $log = new CM_Paging_Log_Mail();
        $log->add($this, $msg);
    }

    protected function _send($subject, $text, $html = null) {
        if (!self::_getConfig()->send) {
            $this->_log($subject, $text);
        } else {
            $mail = new PHPMailer(true);
            $mail->CharSet = 'utf-8';

            foreach ($this->_replyTo as $replyTo) {
                $mail->AddReplyTo($replyTo['address'], $replyTo['name']);
            }
            foreach ($this->_to as $to) {
                $mail->AddAddress($to['address'], $to['name']);
            }
            foreach ($this->_cc as $cc) {
                $mail->AddCC($cc['address'], $cc['name']);
            }
            foreach ($this->_bcc as $bcc) {
                $mail->AddBCC($bcc['address'], $bcc['name']);
            }
            if ($mailDeliveryAgent = $this->_getConfig()->mailDeliveryAgent) {
                $mail->AddCustomHeader('X-MDA: ' . $mailDeliveryAgent);
            }
            $mail->SetFrom($this->_sender['address'], $this->_sender['name']);

            $mail->Subject = $subject;
            $mail->IsHTML($html);
            $mail->Body = $html ? $html : $text;
            $mail->AltBody = $html ? $text : '';

            try {
                $mail->Send();
            } catch (phpmailerException $e) {
                throw new CM_Exception_Invalid('Cannot send email, phpmailer reports: ' . $e->getMessage());
            }
        }
    }

    /**
     * @param CM_Model_Language|null $language
     * @return array array($subject, $html, $text)
     */
    protected function _render($language) {
        $render = new CM_Frontend_Render($this->_site, $this->_recipient, $language);
        $renderAdapter = new CM_RenderAdapter_Mail($render, $this);
        return $renderAdapter->fetch();
    }
}
