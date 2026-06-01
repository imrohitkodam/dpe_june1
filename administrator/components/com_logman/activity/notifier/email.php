<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Activity E-mail Notifier
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */
#[\AllowDynamicProperties]
class ComLogmanActivityNotifierEmail extends ComLogmanActivityNotifierAbstract
{
    /**
     * The E-mail recipients.
     *
     * @var array
     */
    protected $_recipients = array();

    /**
     * The E-mail sender
     *
     * @var array
     */
    protected $_sender;

    /**
     * The E-mail attachments.
     *
     * @var array
     */
    protected $_attachments = array();

    /**
     * The E-mail Carbon Copy data.
     *
     * @var array
     */
    protected $_cc = array();

    /**
     * The E-mail Blind Carbon Copy data.
     *
     * @var array
     */
    protected $_bcc = array();

    /**
     * The E-mail Reply To data.
     *
     * @var array
     */
    protected $_replyto = array();

    /**
     * Tells if the E-mail's type is HTML.
     *
     * @var bool
     */
    protected $_html;

    /**
     * The E-mail subject.
     *
     * @var string
     */
    protected $_subject;

    /**
     * The E-mail body.
     *
     * @var string
     */
    protected $_body;

    /**
     * E-mail filter.
     *
     * @var KFilterInterface
     */
    protected $_filter;

    /**
     * E-mail template URL.
     *
     * @var string
     */
    protected $_template;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->setRecipients(KObjectConfig::unbox($config->recipients));
        $this->setBCC(KObjectConfig::unbox($config->bcc));
        $this->setCC(KObjectConfig::unbox($config->cc));
        $this->setReplyto(KObjectConfig::unbox($config->replyto));
        $this->setSender($config->sender->email, $config->sender->name);
        $this->setSubject($config->subject);
        $this->setAttachments(KObjectConfig::unbox($config->attachments));
        $this->setHtml($config->html);

        $this->_template = $config->template;
    }

    protected function _initialize(KObjectConfig $config)
    {
        $translator  = $this->getObject('translator');
        $site_config = \Joomla\CMS\Factory::getConfig();

        $config->append(array(
            'html'        => true,
            'attachments' => array(),
            'recipients'  => array(),
            'sender'      => array(
                'email' => $site_config->get('mailfrom', 'noreply@site.com'),
                'name'  => $site_config->get('fromname', '')
            ),
            'subject'     => $translator->translate('New activity notification'),
            'replyto'     => array(),
            'bcc'         => array(),
            'cc'          => array(),
            'template'    => 'com://admin/logman.activity.email.html'
        ));

        parent::_initialize($config);
    }

    public function notify(ComActivitiesActivityInterface $activity)
    {
        $result = false;

        if ($this->_canNotify($activity))
        {
            $mail = \Joomla\CMS\Factory::getMailer();

            if ($recipients = $this->getRecipients()) {
                $mail->addRecipient($recipients);
            }

            $mail->setSubject($this->getSubject());

            if (!$this->getBody())
            {
                $template = $this->_getTemplate();

                $template->registerFunction('url', array($this, 'getUrl'));

                $body = $template->render(array(
                    'recipients'  => $this->getRecipients(),
                    'attachments' => $this->getAttachments(),
                    'cc'          => $this->getCC(),
                    'bcc'         => $this->getBCC(),
                    'html'        => $this->isHtml(),
                    'reply_to'    => $this->getReplyto(),
                    'sender'      => $this->getSender(),
                    'activity'    => $activity
                ));

                $this->setBody($body);
            }

            $mail->setBody($this->getBody());

            if ($attachments = $this->getAttachments())
            {
                foreach ($attachments as $file => $config) {
                    $mail->addAttachment($file, $config['name'], $config['encoding'], $config['mimetype']);
                }
            }

            foreach ($this->getCC() as $email => $name)
            {
                if ($email === $name) {
                    $mail->addCC($email);
                }
                else $mail->addCC($email, $name);
            }

            foreach ($this->getBCC() as $email => $name)
            {
                if ($email === $name) {
                    $mail->addBCC($email);
                }
                else $mail->addBCC($email, $name);
            }

            if ($this->isHtml()) {
                $mail->isHtml(true);
            }

            if ($replyto = $this->getReplyto())
            {
                foreach ($replyto as $email => $name) {
                    $mail->addReplyTo($email, $name);
                }
            }

            $sender = $this->getSender();

            $mail->setSender(array($sender['email'], $sender['name'], empty($replyto) ? true : false));



            $result = $mail->Send();

            if (!is_bool($result)) {
                $result = false;
            }
        }

        return $result;
    }

    protected function _getTemplate()
    {
        $template = $this->getObject('com://admin/logman.view.html')->getTemplate();

        return $template->loadFile($this->_template);
    }

    /**
     * Tells if the current notifier can send notifications.
     *
     * @return bool True is it can, false otherwise.
     */
    protected function _canNotify(ComActivitiesActivityInterface $activity)
    {
        return $this->getRecipients() || $this->getBCC() || $this->getCC();
    }

    /**
     * Recipients setter.
     *
     * @param array|string $recipients The E-mail recipients.
     * @param bool         $merge      Whether or not to merge with the existing data.
     * @return ComLogmanActivityNotifierEmail
     */
    public function setRecipients($recipients, $merge = true)
    {
        $recipients = $this->_getEmails($recipients);

        if ($merge) {
            $this->_recipients = array_merge($this->_recipients, $recipients);
        }
        else $this->_recipients = $recipients;

        return $this;
    }

    /**
     * Recipients getter.
     *
     * @return array The E-mail recipients.
     */
    public function getRecipients()
    {
        return $this->_recipients;
    }

    /**
     * CC setter.
     *
     * @param array|string $cc    The E-mail CC data.
     * @param bool         $merge Whether or not to merge with the existing data.
     * @return ComLogmanActivityNotifierEmail
     */
    public function setCC($cc, $merge = true)
    {
        $cc = $this->_getEmails($cc);

        if ($merge) {
            $this->_cc = array_merge($this->_cc, $cc);
        }
        else $this->_cc = $cc;

        return $this;
    }

    /**
     * CC getter.
     *
     * @return array The E-mail CC data.
     */
    public function getCC()
    {
        return $this->_cc;
    }

    /**
     * BBC setter.
     *
     * @param array|string $bcc   The E-mail BCC data.
     * @param bool         $merge Whether or not to merge with the existing data.
     * @return ComLogmanActivityNotifierEmail
     */
    public function setBCC($bcc, $merge = true)
    {
        $bcc = $this->_getEmails($bcc);

        if ($merge) {
            $this->_bcc = array_merge($this->_bcc, $bcc);
        }
        else $this->_bcc = $bcc;

        return $this;
    }

    /**
     * BCC getter.
     *
     * @return array The E-mail BCC data.
     */
    public function getBCC()
    {
        return $this->_bcc;
    }

    /**
     * Sender setter.
     *
     * @param string $email The sender's E-mail.
     * @param string $name  The sender's name.
     * @return ComLogmanActivityNotifierEmail
     */
    public function setSender($email, $name = '')
    {
        $this->_sender = array();

        $this->_sender['email'] = (string) $email;

        if (!$name) {
            $name = $this->_sender['email'];
        }

        $this->_sender['name'] = (string) $name;

        return $this;
    }

    /**
     * Sender getter.
     *
     * @return array Associative array containing the sender's information.
     */
    public function getSender()
    {
        return $this->_sender;
    }

    /**
     * Reply To setter.
     *
     * @param array|string $replyto The E-mail Reply To data.
     * @param bool         $merge   Whether or not to merge with the existing data.
     * @return ComLogmanActivityNotifierEmail
     */
    public function setReplyto($replyto , $merge = true)
    {
        $replyto = $this->_getEmails($replyto);

        if ($merge) {
            $this->_replyto = array_merge($this->_replyto, $replyto);
        }
        else $this->_replyto = $replyto;

        return $this;
    }

    /**
     * Reply To getter.
     *
     * @return array Associative array containing the E-mail reply to data.
     */
    public function getReplyto()
    {
        return $this->_replyto;
    }

    /**
     * E-mails getter.
     *
     * @param array|string $data The E-mails.
     * @return array The associative data.
     */
    protected function _getEmails($data)
    {
        $emails = array();
        $filter = $this->_getFilter();

        foreach ((array) $data as $email => $name)
        {
            if (is_numeric($email)) {
                $email = $name;
            }

            if ($filter->validate($email)) {
                $emails[$email] = $name;
            }
        }

        return $emails;
    }

    /**
     * E-mail filter getter.
     *
     * @return KFilterInterface
     */
    protected function _getFilter()
    {
        if (!$this->_filter instanceof KFilterInterface) {
            $this->_filter = $this->getObject('lib:filter.email');
        }

        return $this->_filter;
    }

    /**
     * Subject setter.
     *
     * @param string $subject The E-mail subject.
     * @return ComLogmanActivityNotifierEmail
     */
    public function setSubject($subject)
    {
        $this->_subject = (string) $subject;
        return $this;
    }

    /**
     * Subject getter.
     *
     * @return string The E-mail subject.
     */
    public function getSubject()
    {
        return $this->_subject;
    }

    /**
     * Body setter.
     *
     * @param string $body The E-mail body.
     * @return ComLogmanActivityNotifierEmail
     */
    public function setBody($body)
    {
        $this->_body = (string) $body;
        return $this;
    }

    /**
     * Body getter.
     *
     * @return string The E-mail body.
     */
    public function getBody()
    {
        return $this->_body;
    }

    /**
     * Attachment setter.
     *
     * @param   array $attachments Associative array containing the E-mail attachments data.
     * @param   bool  $merge       Whether or not to merge with the existing data.
     * @return ComLogmanActivityNotifierEmail
     */
    public function setAttachments($attachments, $merge = true)
    {
        $data = array();

        foreach ((array) $attachments as $attachment => $config)
        {
            if (is_numeric($attachment))
            {
                $attachment = $config;
                $config = array();
            }

            $data[$attachment] = array_merge(array('name' => '', 'encoding' => 'base64', 'mimetype' => ''), $config);
        }

        if ($merge) {
            $this->_attachments = array_merge($this->_attachments, $data);
        }
        else $this->_attachments = $data;

        return $this;
    }

    /**
     * Attachments getter.
     *
     * @return array Associative array containing the E-mail attachment data.
     */
    public function getAttachments()
    {
        return $this->_attachments;
    }

    /**
     * Sets or un-sets the E-mail's type to HTML.
     *
     * @param bool $state If true, the type is set to HTML. Otherwise the type is set to plain text.
     */
    public function setHtml($state = true)
    {
        $this->_html = (bool) $state;
    }

    /**
     * Tells if the E-mail's type is HTML.
     *
     * @return bool True if the E-mail's type is HTML, false otherwise.
     */
    public function isHtml()
    {
        return $this->_html;
    }
}