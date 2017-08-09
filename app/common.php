<?php
/**
 * Created by PhpStorm.
 * User: YHx
 * Date: 2017/8/9
 * Time: 14:47
 */
function calculate_score($opt,$total_score,$change_score){
    switch ($opt){
        case "+":
            $total=$total_score+$change_score;
            break;
        case "-":
            $total=$total_score-$change_score;
            break;
    }
    return $total;
}