<?php
/**
 * @author toph <toph@toph.fr>
 *
 * TfEMail is the legal property of its developers, whose names
 * may be too numerous to list here. Please refer to the AUTHORS file
 * distributed with this source distribution.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class TfEMail {

    var $from;
    var $to;
    var $cc;
    var $bcc;
    var $replyTo;
    var $sender;
    var $returnPath;
    var $subject;
    var $message;
    var $messageHTML;
    var $files;
    var $limit;

    var $DEFAULT_MIME = 'application/unknown';

    var $TYPES_MIME =
        array(
              'avi'   => 'video/msmovie'
            , 'bmp'   => 'image/bmp'
            , 'css'   => 'text/css'
            , 'doc'   => 'application/msword'
            , 'gif'   => 'image/gif'
            , 'htm'   => 'text/html'
            , 'html'  => 'text/html'
            , 'java'  => 'text/plain'
            , 'jpe'   => 'image/jpeg'
            , 'jpeg'  => 'image/jpeg'
            , 'jpg'   => 'image/jpeg'
            , 'js'    => 'text/plain'
            , 'kar'   => 'audio/midi'
            , 'mid'   => 'audio/midi'
            , 'midi'  => 'audio/midi'
            , 'mov'   => 'video/quicktime'
            , 'movie' => 'video/x-sgi-movie'
            , 'mp2'   => 'audio/mpeg'
            , 'mp3'   => 'audio/mpeg'
            , 'mpe'   => 'video/mpeg'
            , 'mpeg'  => 'video/mpeg'
            , 'mpg'   => 'video/mpeg'
            , 'mpga'  => 'audio/mpeg'
            , 'pdf'   => 'application/pdf'
            , 'php'   => 'text/plain'
            , 'php3'  => 'text/plain'
            , 'png'   => 'image/png'
            , 'qt'    => 'video/quicktime'
            , 'ra'    => 'audio/x-pn-realaudio'
            , 'ram'   => 'audio/x-pn-realaudio'
            , 'rm'    => 'audio/x-pn-realaudio'
            , 'rpm'   => 'audio/x-pn-realaudio-plugin'
            , 'rtf'   => 'text/rtf'
            , 'sgm'   => 'text/sgml'
            , 'sgml'  => 'text/sgml'
            , 'tif'   => 'image/tiff'
            , 'tiff'  => 'image/tiff'
            , 'txt'   => 'text/plain'
            , 'xls'   => 'application/vnd.ms-excel'
            , 'xml'   => 'text/xml'
        );

    function __construct($fromEmail=null, $fromName=null) {
        $this->to = array();
        $this->cc = array();
        $this->bcc = array();
        $this->replyTo = array();
        $this->files = array();
        $this->limit = "_NextPart_".md5(uniqid (rand()));
        if($fromEmail) $this->setFrom($fromEmail, $fromName);
    }

    function setFrom($email, $name=null) {
        if($name) {
            $this->from = "\"$name\" <$email>";
        } else {
            $this->from = $email;
        }
    }

    function addTo($email, $name=null) {
        if(!$name) $name = $email;
        $this->to[$email] = $name;
    }

    function addCc($email, $name=null) {
        if(!$name) $name = $email;
        $this->cc[$email] = $name;
    }

    function addBcc($email, $name=null) {
        if(!$name) $name = $email;
        $this->bcc[$email] = $name;
    }

    function addReplyTo($email, $name=null) {
        if(!$name) $name = $email;
        $this->replyTo[$email] = $name;
    }

    function removeTo($email) {
        unset($this->to[$email]);
    }

    function removeCc($email) {
        unset($this->cc[$email]);
    }

    function removeBcc($email) {
        unset($this->bcc[$email]);
    }

    function removeReplyTo($email) {
        unset($this->replyTo[$email]);
    }

    function setSubject($subject) {
        $this->subject = $subject;
    }

    function setMessage($message) {
        $this->message = $message;
    }

    function setMessageHTML($messageHTML) {
        $this->messageHTML = $messageHTML;
        if(!$this->message) {
            $this->message = strip_tags($messageHTML);
        }
    }

    function addFiles($files) {
        foreach($files as $name => $path) {
            $this->addFile($path, is_numeric($name)?null:$name);
        }
    }

    function addFile($path, $fileName=null) {
        if(!$fileName) $fileName = basename($path);
        $this->files[] = array('name'=>$fileName, 'path'=>$path);
    }

    function addFileContent($fileName, $content) {
        $this->files[] = array('name'=>$fileName, 'content'=>$content);
    }

    function getToInLine() {
        return $this->_getListInLine($this->to);
    }

    function _getListInLine($list) {
        $to = '';
        foreach($list as $email => $name) {
            if($to) $to .= ',';
            if($name && $email != $name) {
                $to .= "\"$name\" <$email>";
            } else {
                $to .= "$email";
            }
        }
        return $to;
    }

    /**
     * @param $to string
     * @param $file string (optionnel) fichier dans lequel est sauv� le contenu du mail
     * @return bool
    */
    function send($to='', $file='') {
        $head = $this->makeHead();
        $body = $this->makeBody();
        if(!$to) $to = $this->getToEMailInLine();
        if($file) {
            $fd = fopen($file, 'w');
            if($fd) {
                fwrite($fd, $head);
                fwrite($fd, "\n");
                fwrite($fd, $body);
                fclose($fd);
            }
        }
        return mail($to, $this->encode_subject($this->subject), $body, $head);
    }

    function makeHead() {
        $cc = $this->getCcInLine();
        $bcc = $this->getBccInLine();
        $replyTo = $this->getReplyToInLine();

        $head = '';
        //$head = "To: ".$this->getToInLine()."\n";
        if($cc) $head .= "Cc: $cc\n";
        if($bcc) $head .= "Bcc: $bcc\n";
        if($replyTo) $head .= "Reply-to: $replyTo\n";
        $head .= "From: ".$this->from." \n";
        $head .= "X-Sender: ".$this->getSender()." \n";
        $head .= "Return-Path: ".$this->getReturnPath()." \n";
        $head .= "MIME-Version: 1.0\n";
        $head .= "X-Mailer: TfEMail PHP Class\n";
        //$head .= "content-class: urn:content-classes:message\n";
        $head .= "Content-Type: multipart/mixed;\n";
        $head .= " boundary=\"----=".$this->limit."\"\n";
        //$head .= "X-Priority: 3 \n";
        return $head;
    }

    function getCcInLine() {
        return $this->_getListInLine($this->cc);
    }

    function getBccInLine() {
        return $this->_getListInLine($this->bcc);
    }

    function getReplyToInLine() {
        return $this->_getListInLine($this->replyTo);
    }

    function getSender() {
        if($this->sender) return $this->sender;
        return $this->from;
    }

    function getReturnPath() {
        if($this->returnPath) return $this->returnPath;
        return $this->from;
    }

    function setReturnPath($email, $name=null) {
        if($name) {
            $this->returnPath = "\"$name\" <$email>";
        } else {
            $this->returnPath = $email;
        }
    }

    function makeBody() {

        $body = "This is a multi-part message in MIME format.\n";

        if($this->messageHTML) {
            $body .= "\n------=".$this->limit."\n";
            $body .= "Content-Type: text/html; charset=\"iso-8859-1\"\n";
            //$body .= "Content-Transfer-Encoding: base64\n\n";
            //$body .= chunk_split(base64_encode($this->messageHTML));
            //$body .= "Content-Transfer-Encoding: quoted-printable\n\n";
            $body .= "Content-Transfer-Encoding: 8bit\n\n";
            $body .= $this->messageHTML;
            $body .= "\n";
        } else {
            $body .= "\n------=".$this->limit."\n";
            $body .= "Content-Type: text/plain; charset=\"iso-8859-1\"\n";
            //$body .= "Content-Transfer-Encoding: base64\n\n";
            //$body .= chunk_split(base64_encode($this->message));
            //$body .= "Content-Transfer-Encoding: quoted-printable\n\n";
            $body .= "Content-Transfer-Encoding: 8bit\n\n";
            $body .= $this->message;
            $body .= "\n";
        }

        if(is_array($this->files) && !empty($this->files)) {
            foreach($this->files as $f) {

                $filename = isset($f['name']) ? $f['name'] : 'unnammed';

                $body .= "\n------=".$this->limit."\n";
                $body .= "Content-Type: ".$this->typeMime($filename)."; name=\"$filename\"\n";
                $body .= "Content-Transfer-Encoding: base64\n";
                $body .= "Content-Disposition: attachment;\n      filename=\"$filename\"\n\n";

                if(isset($f['content'])) {
                    $body .= chunk_split(base64_encode($f['content']));
                } elseif(isset($f['path'])) {
                    $contents = file_get_contents($f['path']);
                    //$fdd = fopen($path, "rb");
                    //$contents = fread($fdd, filesize($path));
                    //fclose($fdd);
                    $body .= chunk_split(base64_encode($contents));
                    //$body .= "\n------=".$this->limit."--\n";
                }
            }
            $body .= "\n------=".$this->limit."--\n";
        }
        return $body;
    }

    /** Renvoie le type mime du fichier passé en parametre
     *  si il est connue, $DEFAULT_MIME sinon
     *	@author cg
     *	@param $file string nom du fichier
     *	@return type-mime
    */
    function typeMime($file) {
        return $this->typeMimeOfExt(substr(strrchr($file,"."),1));
    }

/** Renvoie le type mime correspondant à l'extention passée en parametre
     *  si elle est connue, $DEFAULT_MIME sinon
     *	@author cg
     *	@param $ext string extension de fichier
     *	@return type-mime
    */
    function typeMimeOfExt($ext) {
        $ext = strtolower($ext);
        if(array_key_exists($ext, $this->TYPES_MIME)) {
            return $this->TYPES_MIME[$ext];
        }
        return $this->DEFAULT_MIME;

    }

    function getToEMailInLine() {
        return $this->_getEMailListInLine($this->to);
    } //end_typeMime()

    function _getEMailListInLine($list) {
        $to = '';
        foreach($list as $email => $name) {
            if($to) $to .= ',';
            $to .= $email;
        }
        return $to;
    }

    function encode_subject($s) {
        $r = '=?iso-8859-1?Q?';
        for($i=0;$i<strlen($s);$i++) {
            $c = $s[$i];
            $a = ord($c);
            if($a>ord('z')) {
                $r .= sprintf('=%02x', ord($c));
            } else {
                $r .= $c;
            }
        }
        return $r.'?=';
    }

}
