<?php
/**
 * setReplyToToken Plugin for LimeSurvey
 * Set the Reply-To when sending email for token : use Bounce email
 *
 * @author Denis Chenu <denis@sondages.pro>
 * @copyright 2016 Valore Formazione <http://www.valoreformazione.it/>
 * @copyright 2016 Denis Chenu <http://sondages.pro>
 * @license GPL v3
 * @version 1.1.0
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 */

class setReplyToToken extends PluginBase {
    static protected $description = 'Set the Reply-To when sending email for token : use Bounce email.';
    static protected $name = 'setReplyToToken';

    /**
    * Actual sid/surveyid
    */
    private $iSurveyId;
    /**
    * Get the final error to set to debug
    */
    private $mailError="";


    /* Not able to use init in 2.05_plus_150413 */
    //~ public function init()
    //~ {
    //~ $this->subscribe('beforeTokenEmail','setReplyToSend');
    //~ }
    public function __construct(PluginManager $manager, $id) {
        parent::__construct($manager, $id);
        $this->subscribe('beforeTokenEmail','setReplyToSend');
    }
    /**
    * Set the Reply-To when sending an email for token (invite/remind, register if possible)
    * Construct the email and send it
    * Use event to set send to true (or not)
    */
    public function setReplyToSend()
    {
        $oEvent=$this->event;
        $Bounce=$oEvent->get('bounce');
        if(filter_var($Bounce, FILTER_VALIDATE_EMAIL)){
            $this->iSurveyId=intval(App()->request->getParam('surveyid',App()->request->getParam('sid')));// Old API : 2.51.5 $oEvent->get('survey')
            $this->SendEmailMessage();
            if($this->mailError!=""){
                $oEvent->set('error',$this->mailError);
            }
            $oEvent->set('send',false);
        }
    }

  /**
   * Send email API dependant
   */
  private function SendEmailMessage()
  {
    if (Yii::app()->getConfig('demoMode'))
    {
        $this->mailerror=gT('Email was not sent because demo-mode is activated.');
    }
    $oEvent=$this->event;
    $oToken=$oEvent->get('token');
    $oSurvey=Survey::model()->findByPk($this->iSurveyId);
    require_once(APPPATH.'/third_party/phpmailer/class.phpmailer.php');
    $oMail = new PHPMailer;
    $bSmtpDebug = Yii::app()->getConfig("emailsmtpdebug");

    $aTo=$oEvent->get('to');
    /* construction of senders */
    $fromName='';
    $fromMail=$oEvent->get('from');
    if (strpos($fromMail,'<'))
    {
        $fromName=trim(substr($fromMail,0, strpos($fromMail,'<')-1));
        $fromMail=substr($fromMail,strpos($fromMail,'<')+1,strpos($fromMail,'>')-1-strpos($fromMail,'<'));
    }
    $bounceName='';
    $bounceEmail=$oEvent->get('bounce');
    if (strpos($bounceEmail,'<'))
    {
        $bounceName=trim(substr($bounceEmail,0, strpos($bounceEmail,'<')-1));
        $bounceEmail=substr($bounceEmail,strpos($bounceEmail,'<')+1,strpos($bounceEmail,'>')-1-strpos($bounceEmail,'<'));
    }
    $replytoName=($bounceName!="") ? $bounceName : $fromName;
    $replytoEmail=$bounceEmail;

    /* Set by global settings */
    switch (Yii::app()->getConfig('emailmethod')) {
        case "qmail":
            $oMail->IsQmail();
            break;
        case "smtp":
            $oMail->IsSMTP();
            if (Yii::app()->getConfig("emailsmtpdebug")>0)
            {
                $oMail->SMTPDebug = Yii::app()->getConfig("emailsmtpdebug");
            }
            $emailsmtphost = Yii::app()->getConfig("emailsmtphost");
            if (strpos($emailsmtphost,':')>0)
            {
                $oMail->Host = substr($emailsmtphost,0,strpos($emailsmtphost,':'));
                $oMail->Port = substr($emailsmtphost,strpos($emailsmtphost,':')+1);
            }
            else {
                $oMail->Host = $emailsmtphost;
            }
            $oMail->Username =Yii::app()->getConfig("emailsmtpuser");
            $emailsmtppassword = Yii::app()->getConfig("emailsmtppassword");
            $oMail->Password =$emailsmtppassword;
            if (trim($emailsmtppassword)!="")
            {
                $oMail->SMTPAuth = true;
            }
            $emailsmtpssl = Yii::app()->getConfig("emailsmtpssl");
            if (isset($emailsmtpssl) && trim($emailsmtpssl)!=='' && $emailsmtpssl!==0) {
                if ($emailsmtpssl===1) {
                    $mail->SMTPSecure = "ssl";
                }else {
                    $mail->SMTPSecure = $emailsmtpssl;
                }
            }
            break;
        case "sendmail":
            $oMail->IsSendmail();
            break;
        case "mail":
        default:
            $oMail->IsMail();
    }
    $emailcharset = Yii::app()->getConfig("emailcharset");
    $sitename=Yii::app()->getConfig("sitename");
    $sBody=$oEvent->get('body');
    $sSubject=$oEvent->get('subject');
    if ($emailcharset!='utf-8')
    {
        $sBody=mb_convert_encoding($sBody,$emailcharset,'utf-8');
        $sSubject=mb_convert_encoding($sSubject,$emailcharset,'utf-8');
        $sitename=mb_convert_encoding($sitename,$emailcharset,'utf-8');
    }
    $oMail->CharSet = $emailcharset;

    /* From and sender */
    $oMail->SetFrom($fromMail, $fromName);
    $oMail->addReplyTo($replytoEmail, $replytoName);
    $oMail->Sender = $bounceEmail; // Sets Return-Path for error notifications

    /* Custom headers */
    $oMail->AddCustomHeader("X-Surveymailer: $sitename Emailer (LimeSurvey.sourceforge.net)");
    if($this->iSurveyId){
        $oMail->AddCustomHeader("X-surveyid: " . $this->iSurveyId);
    }
    if($oEvent->get('token')){
        $oMail->AddCustomHeader("X-tokenid: " . $oToken->token);
    }

    /* To */
    foreach ($aTo as $sTo)
    {
        if (strpos($sTo, '<') )
        {
            $toEmail=substr($sTo,strpos($sTo,'<')+1,strpos($sTo,'>')-1-strpos($sTo,'<'));
            $toName=trim(substr($sTo,0, strpos($sTo,'<')-1));
            $oMail->AddAddress($toEmail,$toName);
        }
        else
        {
            $oMail->AddAddress($sTo);
        }
    }
    $oMail->Body = $sBody;
    /* html or txt */
    if ($oSurvey->htmlemail == 'Y')
    {
        $oMail->IsHTML(true);
        /* we can always apply same fix than core (Spam issue)*/
        if(strpos($sBody,"<html>")===false)
        {
            $sBody="<html>".$sBody."</html>";
        }
        $oMail->msgHTML($sBody,App()->getConfig("publicdir"));
    }
    else
    {
        $oMail->IsHTML(false);
    }

    /* @todo Attachement */
    $sType=$oEvent->get('type');
    $aRelevantAttachments = array();
    $oSurvey=Survey::model()->findByPk($this->iSurveyId);
    $sLang=$oToken->language;
    if(!in_array($sLang,$oSurvey->getAllLanguages())){
        $sLang=$oSurvey->language;
    }
    $oSurveyLanguage=SurveyLanguageSetting::model()->find("surveyls_survey_id =:sid and surveyls_language=:lang",array(":sid"=>$this->iSurveyId,":lang"=>$sLang));
    tracevar($oSurveyLanguage->attachments);
    if($oSurveyLanguage && $oSurveyLanguage->attachments){
        $aAttachments = unserialize($oSurveyLanguage->attachments);
        if(isset($aAttachments[$sType]) && !empty($aAttachments[$sType])){
            LimeExpressionManager::singleton()->loadTokenInformation($this->iSurveyId, $oToken->token);
            foreach ($aAttachments[$sType] as $aAttachment)
            {
                if (LimeExpressionManager::singleton()->ProcessRelevance($aAttachment['relevance']))
                {
                    $aRelevantAttachments[] = $aAttachment['url'];
                }
            }
        }
    }
    foreach ($aRelevantAttachments as $attachment)
    {
        // Attachment is either an array with filename and attachment name.
        if (is_array($attachment))
        {
            $oMail->AddAttachment($attachment[0], $attachment[1]);
        }
        else
        { // Or a string with the filename.
            $oMail->AddAttachment($attachment);
        }
    }

    /* subject and body */
    if (trim($sSubject)!='') {
        $oMail->Subject = "=?$emailcharset?B?" . base64_encode($sSubject) . "?=";
    }

    if ($bSmtpDebug>0){
        ob_start();
    }
    $bSent=$oMail->Send();
    if($oMail->ErrorInfo){
        $this->mailError.=$oMail->ErrorInfo;
        if ($bSmtpDebug>0) {
            $this->mailError .= '<pre>'.strip_tags(ob_get_contents()).'</pre>';// Must review according to LS API version
        }
    }
    if ($bSmtpDebug>0){
        ob_end_clean();
    }
  }
}
