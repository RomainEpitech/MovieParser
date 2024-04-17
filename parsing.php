<?php

    $filename = $argv[1];
    $htmlContent = file_get_contents($filename);

    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML($htmlContent);
    libxml_clear_errors();

    $metaTags = $dom->getElementsByTagName('meta');
    $type = '';
    foreach ($metaTags as $tag) {
        if ($tag->getAttribute('property') === 'og:type') {
            $type = $tag->getAttribute('content');
            break;
        }
    }

    $jsonOutput = [
        "status" => "ok",
        "result" => [
            "$type" => []
        ]
    ];

    $metaTags = $dom->getElementsByTagName('meta');
    $movieTitle = '';
    foreach ($metaTags as $tag) {
        if ($tag->getAttribute('property') === 'og:title') {
            $movieTitle = $tag->getAttribute('content');
            break;
        }
    }

    $spans = $dom->getElementsByTagName('span');
    $releaseDate = '';
    foreach ($spans as $span) {
        if (strpos($span->getAttribute('class'), 'release_date') !== false) {
            preg_match('/\((\d{4})\)/', $span->textContent, $matches);
            if (!empty($matches)) {
                $releaseDate = $matches[1];
            }
            break;
        }
    }

    $overviewDivs = $dom->getElementsByTagName('div');
    $overviewText = '';
    foreach ($overviewDivs as $div) {
        if ($div->getAttribute('class') === 'overview' && $div->getAttribute('dir') === 'auto') {
            $bTags = $div->getElementsByTagName('p');
            foreach ($bTags as $b) {
                $overviewText = $b->textContent;
                break 2;
            }
        }
    }

    $genres = [];
    $genreSections = $dom->getElementsByTagName('section');
    foreach ($genreSections as $section) {
        if ($section->getAttribute('class') === 'genres right_column') {
            $genreListItems = $section->getElementsByTagName('li');
            foreach ($genreListItems as $li) {
                $genreText = trim($li->textContent);
                if (ctype_upper($genreText[0])) {
                    $genres[] = $genreText;
                }
            }
            break;
        }
    }


    $facts = $dom->getElementsByTagName('p');
    $status = $duration = $budget = $revenue = $originalLanguage = '';
    foreach ($facts as $fact) {
        $content = $fact->textContent;
        if (strpos($content, 'Status') !== false) {
            $status = trim(str_replace('Status', '', $content));
        } elseif (strpos($content, 'Runtime') !== false) {
            $duration = trim(str_replace('Runtime', '', $content));
        } elseif (strpos($content, 'Budget') !== false) {
            $budget = trim(str_replace('Budget', '', $content));
        } elseif (strpos($content, 'Revenue') !== false) {
            $revenue = trim(str_replace('Revenue', '', $content));
        } elseif (strpos($content, 'Original Language') !== false) {
            $originalLanguage = trim(str_replace('Original Language', '', $content));
        }
    }

    $keywords = [];
    $keywordSections = $dom->getElementsByTagName('section');
    foreach ($keywordSections as $section) {
        if ($section->getAttribute('class') === 'keywords right_column') {
            $keywordListItems = $section->getElementsByTagName('li');
            foreach ($keywordListItems as $li) {
                $keywordText = trim($li->textContent);
                $keywords[] = $keywordText;
            }
            break;
        }
    }

    $cast = [];
    $castSection = $dom->getElementsByTagName('section');

    foreach ($castSection as $section) {
        if ($section->getAttribute('class') === 'panel top_billed scroller') {
            $castItems = $section->getElementsByTagName('li');
            foreach ($castItems as $item) {
                $actorLinks = $item->getElementsByTagName('a');
                $actorName = $actorLinks->item(1)->nodeValue;
                
                $characterParas = $item->getElementsByTagName('p');
                $characterName = '';
                foreach ($characterParas as $para) {
                    if ($para->getAttribute('class') === 'character') {
                        $characterName = $para->nodeValue;
                        break;
                    }
                }

                if ($actorName && $characterName) {
                    $cast[] = ["actor" => trim($actorName), "character" => trim($characterName)];
                    
                }
            }
            break;
        }
    }

    if (!empty($movieTitle) && !empty($releaseDate)) {
        $jsonOutput['result'][$type][] = [
            "title" => $movieTitle,
            "releaseDate" => $releaseDate,
            "summary" => $overviewText,
            "status" => $status,
            "duration" => $duration,
            "budget" => $budget,
            "revenue" => $revenue,
            "originalLanguage" => $originalLanguage,
            "genre" => $genres,
            "keywords" => $keywords,
            "cast" => $cast
        ];
    }

    $jsonContent = json_encode($jsonOutput, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    file_put_contents('result.json', $jsonContent);
?>