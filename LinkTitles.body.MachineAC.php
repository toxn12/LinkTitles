<?php
  require dirname(__FILE__) . "/State.php";
  require dirname(__FILE__) . "/MachineAC.php";
  
  class LinkMachineAC {

        public static function linkText($text, $titleList, $myTitle){
            $ac = new MachineAC($titleList);
            $res = $ac->match($text);

            foreach($res as $key => $row){
                $key_len[$key] = mb_strlen($row["keyword"]);
            }
            array_multisort($key_len, SORT_DESC, $res);

            for($i = 0; $i < count($res); $i++){
                $r = $res[$i];

                if(strcmp($r["keyword"], $myTitle) == 0){
                    continue;
                }

                $tmp = mb_substr($text, 0, $r["index"], 'UTF-8');
                $op = mb_substr_count($tmp, "[", 'UTF-8');
                $cl = mb_substr_count($tmp, "]", 'UTF-8');

                if($op != $cl){
                    continue;
                }

                $op = mb_substr_count($tmp, "{{", 'UTF-8');
                $cl = mb_substr_count($tmp, "}}", 'UTF-8');
                
                if($op != $cl){
                        continue;
                }

                $tmp = mb_substr($text, 0, $r["index"]+mb_strlen($r["keyword"]), 'UTF-8');
                $op = mb_substr_count($tmp, "[", 'UTF-8');
                $cl = mb_substr_count($tmp, "]", 'UTF-8');
                
                if($op != $cl){
                    continue;
                }
                
                $op = mb_substr_count($tmp, "{{", 'UTF-8');
                $cl = mb_substr_count($tmp, "}}", 'UTF-8');
                
                if($op != $cl){
                        continue;
                }

                $text = mb_substr($text, 0, $r["index"], 'UTF-8') . '[[' . $r["keyword"] . ']]' . mb_substr($text, $r["index"] + mb_strlen($r["keyword"], 'UTF-8'), mb_strlen($text, 'UTF-8') - $r["index"], 'UTF-8');
                 
                for($j = $i + 1; $j < count($res); $j++){
                    if($res[$j]["index"] > $r["index"]){
                        $res[$j]["index"] += 4;
                    }elseif($res[$j]["index"] == $r["index"]){
                        $res[$j]["index"] += 2;
                    }
                }
            }
            
            $text = preg_replace_callback( "/\<pdf.*\>(.*)\<\/pdf\>/",
                function($matches){
                    $rep=str_replace("[","",$matches[0]);
                    return str_replace("]","",$rep);
                },
                $text);
                        return $text;
                }
    }
