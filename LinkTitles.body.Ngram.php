<?php

    class LinkNgram{
        private static function getNGramHash($text, $n){
            $hash = array();
            for($i = 0;  $i < mb_strlen($text, 'UTF-8') + 1 - $n; $i++){
                $index = mb_substr($text, $i, $n, 'UTF-8');
                            if (!array_key_exists($index, $hash)) { 
                    $hash[$index] = array(); 
                }
                $hash[$index][] = $i;
            }
            return $hash;
        }

        private static function isMatch($text, $pos, $key){
            $tmp = mb_substr($text, $pos, mb_strlen($key, 'UTF-8'), 'UTF-8');
            if(strcmp($tmp, $key) == 0){
                return true;
            }else{
                return false;
            }
        }
        
        public static function linkText($text, $titleList, $myTitle){
            $n = 2;
            $hash = self::getNGramHash($text, $n);

            $linkList = array();
            foreach($titleList as $title){
                if(strcmp($title, $myTitle)==0){
                    continue;
                }

                $hashIdx = mb_substr($title, 0, $n, 'UTF-8');
                if(array_key_exists($hashIdx, $hash)){
                    foreach($hash[$hashIdx] as $pos){
                        if(self::isMatch($text, $pos, $title)){
                            $isExists = false;
                            foreach($linkList as $t){
                                if($t['st_pos'] <= $pos && $pos<=$t['en_pos']){
                                    $isExists = true;
                                    break;
                                }
                                
                                if($t['st_pos'] <= $pos + mb_strlen($title, 'UTF-8') -1 && $pos + mb_strlen($title, 'UTF-8') -1<=$t['en_pos']){
                                    $isExists = true;
                                    break;                                                 
                                }
                            }
                            if(!$isExists){
                                $linkList[] = array('st_pos'=>$pos,
                                                    'en_pos'=>$pos + mb_strlen($title, 'UTF-8') -1,
                                                    'key'=>$title);
                            }
                        }
                    }
                }
            }

            $st_pos = array();
            foreach($linkList as $idx => $val){
                    $st_pos[$idx] = $val['st_pos'];
            }
            array_multisort($st_pos, SORT_ASC, $linkList);

            $cnt = 0;
            foreach($linkList as $l){
                $start =$l['st_pos'] + $cnt * 4;
                $end = $l['en_pos'] + $cnt * 4;

                $tmp = mb_substr($text, 0, $start, 'UTF-8');
                $o = mb_substr_count($tmp, '[', 'UTF-8');
                $c = mb_substr_count($tmp, ']', 'UTF-8');

                if($o != $c){
                    continue;
                }
                
                $o = mb_substr_count($tmp, '{{', 'UTF-8');
                $c = mb_substr_count($tmp, '}}', 'UTF-8');

                if($o != $c){
                    continue;
                }

                $tmp = mb_substr($text, 0, $end, 'UTF-8');
                $o = mb_substr_count($tmp, '[', 'UTF-8');
                $c = mb_substr_count($tmp, ']', 'UTF-8');

                if($o != $c){
                    continue;
                }

                $o = mb_substr_count($tmp, '{{', 'UTF-8');
                $c = mb_substr_count($tmp, '}}', 'UTF-8');

                if($o != $c){
                    continue;
                }

                $text = mb_substr($text, 0, $start, 'UTF-8')
                    . "[[" . $l['key'] . "]]"
                    . mb_substr($text, $end + 1, mb_strlen($text, 'UTF-8') - $end, 'UTF-8');

                $cnt++;
            }

            $text = preg_replace_callback( "/\<pdf.*\>(.*)\<\/pdf\>/",
                                          function($matches){
                                            $rep=str_replace("[","",$matches[0]);
                                            return str_replace("]","",$rep);
                                          },$text);
                        return $text;
        }

        }