<?php
/**
 * Created by PhpStorm.
 * User: Ignatov
 * Date: 20.03.2015
 * Time: 9:58
 */

namespace ShorterNS;

class Shorter {
    /// Путь до текущего файла словаря
    private $dicFileName;
    /// Словарь общепринятых сокращений
    private $dictionary = array();

    public function __construct()
    {
        // Загрузка словаря по умолчанию
        $this->Dictionary('Dictionary.csv');
    }

    /// Сокращает фразу таким образом, чтобы ее длина не превышала $maxLength
    /// В случае неудачи возвращает null
    public function Translate($str, $maxLength)
    {
        $length = mb_strlen($str,'UTF-8');
        if ($length <= $maxLength)
            return $str;
        // Разбиваем строку на слова
        $wordsInfo = $this->Explode($str);
        // Сокращаем слова, имеющие общепринятые сокращения
        if ($this->TranslateByDictionary($wordsInfo, $maxLength)) {
            return $this->Implode($wordsInfo);
        }
        // Если необходимо, сокращаем оставшиеся слова в соответствии с правилами русского языка
        // Начинаем со слова наибольшей длины
        if ($this->TranslateByRules($wordsInfo, $maxLength)) {
            return $this->Implode($wordsInfo);
        }
        // Если и после сокращения по правилам русского языка не позволило добиться желаемого результата,
        // то возвращаем обрезанную до $maxLength строку, не оканчивающуюся на часть слова (только слово целиком)
        // и слово не должно быть длиной меньше 3х символов
        return $this->TranslateByWords($wordsInfo, $maxLength);
    }

    /// Загружает словарь общепринятых сокращений
    /// В случае неудачи возвращет null, в случае успеха - путь до файла словаря
    /// При вызове метода без параметра возвращается путь до текущего файла словаря
    public function Dictionary($fileName = null)
    {
        if ($fileName === null) {
            return $this->dicFileName;
        }
        if (!file_exists($fileName)) {
            return null;
        }
        $data = file_get_contents($fileName);
        if (!$data) {
            return null;
        }
        $rows = preg_split('/\n|\r\n?/', $data);
        $dictionary = array();
        foreach($rows as $row)
        {
            $cells = explode(';',$row);
            if (count($cells) !== 2) {
                continue;
            }
            $value = array('pattern' => $cells[0], 'shorter' => $cells[1]);
            array_push($dictionary, $value);
        }
        $this->dictionary = $dictionary;
        $this->dicFileName = $fileName;
        return $fileName;
    }

    private function TranslateByDictionary(&$wordsInfo, $maxLength)
    {
        $wordsInfoLength = count($wordsInfo);
        for ($i = 0; $i < $wordsInfoLength; $i++)
        {
            $wordInfo = $wordsInfo[$i];
            $word = $wordInfo['word'];
            foreach ($this->dictionary as $dicInfo)
            {
                $pattern = $dicInfo['pattern'];
                $shorter = $dicInfo['shorter'];
                $code = preg_match('/^'.$pattern.'$/u', $word, $matches);
                if ($code !== 1) {
                    continue;
                }
                $wordsInfo[$i]['shortWord'] = $shorter;
                $wordsInfo[$i]['isDictionary'] = true;
                $wordsInfo[$i]['shortingLevel'] = 1;
                break;
            }
            if (mb_strlen($this->Implode($wordsInfo),'UTF-8') <= $maxLength) {
                return true;
            }
        }
        return false;
    }

    private function TranslateByRules(&$wordsInfo, $maxLength)
    {
        $nouns = array('а','А','я','Я','у','У','ю','Ю','ы','Ы','и','И','э','Э','е','Е','ё','Ё','о','О');
        while (true) {
            $avgLength = $this->AvgShortWordLength($wordsInfo);
            $processed = false;
            $wordsInfoLength = count($wordsInfo);
            for ($i = 0; $i < $wordsInfoLength; $i++)
            {
                $wordInfo = $wordsInfo[$i];
                if ($wordInfo['isDictionary'] === true) {
                    continue;
                }
                if (mb_strlen($wordInfo['shortWord'],'UTF-8') < $avgLength) {
                    continue;
                }
                $word = $wordInfo['word'];
                $length = mb_strlen($word,'UTF-8');
                // Получаем индексы положения всех гласных в слове
                $indexes = array();
                for ($j = $length - 1; $j >= 0; $j-- ) {
                    if (in_array(mb_substr($word, $j, 1), $nouns, true)) {
                        array_push($indexes, $j);
                    }
                }
                if (count($indexes) === 0) {
                    continue;
                }
                $shortWord = mb_substr($word, 0, $indexes[$wordInfo['shortingLevel']]);
                if (mb_strlen($shortWord,'UTF-8') < 3) {
                    continue;
                }
                // Удаляем гласные с конца, если такие имеются
                while (true)
                {
                    if (mb_strlen($shortWord,'UTF-8') >= 3 &&
                        in_array(mb_substr($shortWord, mb_strlen($shortWord) - 1, 1), $nouns, true)) {
                        $shortWord = mb_substr($shortWord, 0, mb_strlen($shortWord, 'UTF-8') - 1);
                    }
                    else {
                        break;
                    }
                }
                if (mb_strlen($shortWord,'UTF-8') < 3) {
                    continue;
                }
                // Если две и более последние буквы сокращенного варианта одинаковые, то оставляем только одну
                while (true)
                {
                    if (mb_strlen($shortWord,'UTF-8') >= 3 &&
                        mb_substr($shortWord, mb_strlen($shortWord) - 1, 1) ===
                        mb_substr($shortWord, mb_strlen($shortWord) - 2, 1)) {
                        $shortWord = mb_substr($shortWord, 0, mb_strlen($shortWord,'UTF-8') - 1);
                    }
                    else {
                        break;
                    }
                }
                if (mb_strlen($shortWord,'UTF-8') < 3) {
                    continue;
                }
                /// В сокращенном слове должна быть хоть одна гласная
                $hasNouns = false;
                for ($j = mb_strlen($shortWord,'UTF-8') - 1; $j >= 0; $j-- ) {
                    if (in_array(mb_substr($shortWord, $j, 1), $nouns, true)) {
                        $hasNouns = true;
                    }
                }
                if (!$hasNouns) {
                    continue;
                }
                $wordsInfo[$i]['shortWord'] = $shortWord.'.';
                $wordsInfo[$i]['shortingLevel']++;
                $processed = true;
                if (mb_strlen($this->Implode($wordsInfo),'UTF-8') <= $maxLength) {
                    return true;
                }
            }
            if (!$processed) {
                return false;
            }
        }
        return false;
    }

    private function TranslateByWords($wordsInfo, $maxLength)
    {
        $result = '';
        $prevWordLength = 0;
        $prevIsDictionary = false;
        foreach ($wordsInfo as $wordInfo) {
            $word = $wordInfo['word'];
            if ($wordInfo['isDictionary'])
                $word = $wordInfo['shortWord'];
            if (mb_strlen($result.' '.$word,'UTF-8') < $maxLength) {
                $result .= ' '.$word;
                $result = trim($result);
                $prevWordLength = mb_strlen($word,'UTF-8');
                $prevIsDictionary = $wordInfo['isDictionary'];
            } else
            {
                if ($prevWordLength < 3 && $prevIsDictionary === false) {
                    $result = mb_substr($result, 0, mb_strlen($result,'UTF-8') - $prevWordLength - 1);
                }
                if ($result !== '') {
                    return $result;
                }
                break;
            }
        }
        return mb_substr($wordsInfo[0]['word'], 0, $maxLength);
    }

    private function Explode($str)
    {
        $words = explode(' ',trim($str));
        $wordsInfo = array();
        foreach ($words as $word)
        {
            array_push($wordsInfo, array(
                'word' => $word,
                'shortWord' => $word,
                'isDictionary' => false,
                'shortingLevel' => 0));
        }
        return $wordsInfo;
    }

    private function AvgShortWordLength($wordsInfo)
    {
        $sumLength = 0;
        foreach ($wordsInfo as $wordInfo) {
            $sumLength += mb_strlen($wordInfo['shortWord'],'UTF-8');
        }
        return round($sumLength / count($wordsInfo));
    }

    private function Implode($wordsInfo)
    {
        $result = '';
        foreach ($wordsInfo as $wordInfo)
            $result .= ' '.$wordInfo['shortWord'];
        return ltrim($result);
    }
} 