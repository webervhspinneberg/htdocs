<?php if( !defined('IN_WISY') ) die('!IN_WISY');

loadWisyClass('WISY_FRAMEWORK_CLASS');

class COOKIE_FRAMEWORK_CLASS extends WISY_FRAMEWORK_CLASS
{
        function addCConsentOption($name, $cookieOptions) {
            $cookie_essentiell = intval($this->iniRead("cookiebanner.zustimmung.{$name}.essentiell", 0));
            $expiration = $cookieOptions['cookie']['expiryDays'];
            $iniCookietext = 'cookiebanner.zustimmung.'.$name.'.text';
            $text = $this->iniRead($iniCookietext, '');

            $details = "<span class='cookies_techdetails inactive'><br>Speicherdauer:".$expiration." Tage, {$text}".($name == 'analytics' ? ', Name: _pk_ref (Speicherdauer: 6 Monate), Name: _pk_cvar (Speicherdauer: 30min.), Name: _pk_id (Speicherdauer: 13 Monate), Name: _pk_ses (Speicherdauer: 30min.)': '').'</span>';
            // print_r($cookieOptions['cookie']); die("ok");
            return "<li class='{$name} ".($cookie_essentiell == 2 ? "disabled" : "")."'>
                                    <input type='checkbox' name='cconsent_{$name}' "
                                    .(($cookie_essentiell || $_COOKIE['cconsent_'.$name] == 'allow') ? "checked='checked'" : "")
                                    .($cookie_essentiell == 2 ? "disabled" : "")
                                    ."> "
                                    ."<div class='consent_option_infos'>"
                                    .$cookieOptions["content"]["zustimmung_{$name}"]
                                    ."<span class='importance'>"
                                    .($cookie_essentiell === 1 ? '<span class="consent_option_importance"><br>(essentiell)</span>' : ($cookie_essentiell == 2 ? '<span class="consent_option_importance"><br>(technisch notwendig)</span>' : '<span class="consent_option_importance"><br><b>(optional'.($_COOKIE['cconsent_'.$name] == 'allow' ? ' - aktiv zugestimmt' : '').')</b></span>')).$details.'</span>'
                                    .'</div>'
                                    ."</li>";
        }

};

registerWisyClass('COOKIE_FRAMEWORK_CLASS');