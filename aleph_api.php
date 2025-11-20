<?php
// aleph_api.php - API pentru integrare Aleph cu fallback automat

// ✅ NOU - Verifică dacă sesiunea este deja activă înainte de a o porni
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';
require_once 'auth_check.php';

$ALEPH_SERVER = "83.146.133.42";
$ALEPH_PORT = "8991";
$ALEPH_BASE_URL = "http://{$ALEPH_SERVER}:{$ALEPH_PORT}/F";

/**
 * Helper function pentru fetch URL cu timeout (fără conversie automată)
 */
function fetch_url($url, $timeout = 10) {
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($result === false || $http_code !== 200) {
            throw new Exception("Nu se poate accesa: " . $url . ($error ? " - " . $error : ""));
        }
        
        return $result; // Returnează RAW - fără conversie
    } else {
        $context = stream_context_create([
            'http' => [
                'timeout' => $timeout,
                'follow_location' => 1
            ]
        ]);
        
        $result = @file_get_contents($url, false, $context);
        
        if ($result === false) {
            throw new Exception("Nu se poate accesa: " . $url);
        }
        
        return $result; // Returnează RAW - fără conversie
    }
}

/**
 * Convertește encoding de la Aleph la UTF-8 (versiune îmbunătățită)
 * IMPORTANT: Verifică ÎNTÂI dacă textul e deja UTF-8 - dacă DA, nu face conversie!
 */
function convertAlephEncoding($text) {
    if (empty($text)) return $text;
    
    // 🔥 IMPORTANT: Verifică dacă e deja UTF-8 valid
    if (mb_check_encoding($text, 'UTF-8')) {
        // Deja UTF-8 valid - NU converti nimic!
        return $text;
    }
    
    // Dacă nu e UTF-8, încearcă conversie din ISO-8859-2
    $converted = @iconv('ISO-8859-2', 'UTF-8//TRANSLIT//IGNORE', $text);
    
    if ($converted !== false && mb_check_encoding($converted, 'UTF-8')) {
        return $converted;
    }
    
    // Fallback: returnează originalul
    return $text;
}

/**
 * Funcție principală de căutare în Aleph cu fallback automat
 */
function cautaCarteInAleph($search_term, $search_type = 'AUTO') {
    global $ALEPH_BASE_URL;
    
    $debug_info = [];
    
    try {
        // 1. Inițializare sesiune
        $init_url = "{$ALEPH_BASE_URL}?func=file&file_name=find-b";
        $debug_info['init_url'] = $init_url;
        
        $session_response = fetch_url($init_url);
        
        // DEBUGGING - salvează răspunsul brut
        @file_put_contents('debug_session_raw.html', $session_response);
        $debug_info['session_response_length'] = strlen($session_response);
        $debug_info['session_response_preview'] = substr($session_response, 0, 500);
        
        // NU convertim session_response - session ID-ul e ASCII pur
        // Încearcă să extragă session ID din răspunsul RAW
        preg_match('/\/F\/([A-Z0-9\-]+)\?/', $session_response, $matches);
        $session_id = $matches[1] ?? '';
        
        // Dacă nu găsește, încearcă pattern alternativ
        if (empty($session_id)) {
            preg_match('/\/F\/([A-Z0-9\-]+)/', $session_response, $matches);
            $session_id = $matches[1] ?? '';
        }
        
        if (empty($session_id)) {
            // Debugging extins
            $debug_info['session_response_sample'] = substr($session_response, 0, 1000);
            $debug_info['all_matches'] = [];
            preg_match_all('/\/F\/[^"\'>\s]+/', $session_response, $all_matches);
            if (!empty($all_matches[0])) {
                $debug_info['all_matches'] = array_slice($all_matches[0], 0, 10);
            }
            throw new Exception("Nu s-a putut extrage session ID. Verifică debug_session_raw.html pentru detalii.");
        }
        
        $debug_info['session_id'] = $session_id;
        
        // 🔥 DETECTARE AUTOMATĂ: Verifică dacă este cota sau barcode
        if ($search_type === 'AUTO') {
            // Detectează dacă este barcode (format: literă urmată imediat de cifre SAU doar cifre, ex: C013121, 000029152-10)
            // Barcode: literă urmată imediat de cifre (fără cratimă/spațiu între) SAU doar cifre
            if (preg_match('/^([A-Z]\d{5,}|[A-Z]{2,3}\d{4,}|\d{5,})(-\d{1,2})?$/i', $search_term)) {
                // Este barcode - folosește BAR primul
                $search_strategies = ['BAR', 'LOC', 'WRD'];
                $debug_info['detected_type'] = 'barcode';
            }
            // Detectează dacă este cota (format: literă-cifre cu cratimă/spațiu, ex: I-14156, I 14156, II-01270)
            // Cota: începe cu 1-3 litere, apoi cratimă sau spațiu, apoi cifre
            elseif (preg_match('/^[A-Z]{1,3}[\s\-]\d+([\s\-]\d+)?$/i', $search_term)) {
                // Este cota - folosește LOC primul
                $search_strategies = ['LOC', 'BAR', 'WRD'];
                $debug_info['detected_type'] = 'cota';
            } else {
                // Nu se poate determina - încearcă toate
                $search_strategies = ['BAR', 'LOC', 'WRD'];
                $debug_info['detected_type'] = 'unknown';
            }
        } elseif ($search_type === 'BAR') {
            $search_strategies = ['BAR', 'WRD'];
        } elseif ($search_type === 'LOC') {
            $search_strategies = ['LOC', 'WRD'];
        } else {
            $search_strategies = [$search_type];
        }
        
        $search_response = null;
        $used_strategy = null;
		
		// DUPĂ linia cu "used_strategy"
if ($used_strategy === 'BAR' || $used_strategy === 'LOC') {
    // Salvează răspunsul pentru debugging
    file_put_contents('debug_aleph_response.html', $search_response);
}
        
        // Încearcă fiecare strategie până găsește
        foreach ($search_strategies as $strategy) {
            $search_url = "{$ALEPH_BASE_URL}/{$session_id}?func=find-b&request=" . urlencode($search_term) . "&find_code={$strategy}&adjacent=N&local_base=RAI01";
            $debug_info["search_url_{$strategy}"] = $search_url;
            
            $temp_response = fetch_url($search_url);
            // NU convertim aici - folosim doar pentru verificare "no results"
            $debug_info["search_response_{$strategy}_length"] = strlen($temp_response);
            
            // Verifică dacă sunt rezultate
            $no_results = (
                stripos($temp_response, 'Your search found no results') !== false ||
                stripos($temp_response, 'Căutarea nu a avut rezultate') !== false ||
                stripos($temp_response, 'nu a avut rezultate') !== false ||
                stripos($temp_response, 'No results') !== false
            );
            
            if (!$no_results) {
                // GĂSIT!
                $search_response = $temp_response;
                $used_strategy = $strategy;
                $debug_info['used_strategy'] = $strategy;
                break;
            }
        }
        
        if ($search_response === null) {
            return [
                'success' => false,
                'mesaj' => "Nu s-au găsit rezultate pentru: {$search_term} (încercat: " . implode(', ', $search_strategies) . ")",
                'debug' => $debug_info
            ];
        }

// 🔥 NOU - Verifică dacă există deja o carte în baza de date locală cu acea cotă (DOAR când se caută după cotă)
$barcode_pentru_cautare = null;
$necesita_cautare_dupa_barcode = false;

// Aplică logica DOAR pentru căutare după cotă (LOC), NU pentru barcode (BAR)
if ($used_strategy === 'LOC' && ($debug_info['detected_type'] ?? '') === 'cota') {
    try {
        global $pdo;
        // Verifică dacă există o carte în baza de date locală cu acea cotă
        $stmt = $pdo->prepare("SELECT cod_bare FROM carti WHERE cota = ? LIMIT 1");
        $stmt->execute([$search_term]);
        $carte_bd = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($carte_bd && !empty($carte_bd['cod_bare'])) {
            $barcode_pentru_cautare = $carte_bd['cod_bare'];
            $necesita_cautare_dupa_barcode = true;
            $debug_info['barcode_din_bd'] = $barcode_pentru_cautare;
        }
    } catch (Exception $e) {
        $debug_info['bd_check_error'] = $e->getMessage();
    }
    
    // Dacă nu există barcode în BD, extrage barcode-urile din rezultatele Aleph
    if (empty($barcode_pentru_cautare)) {
        // Extrage toate link-urile către item-global din pagina de rezultate cu ACAD
        preg_match_all('/<A\s+[^>]*HREF\s*=\s*["\']?([^"\'>\s]*func=item-global[^"\'>\s]*sub_library=ACAD[^"\'>\s]*)["\']?/i', $search_response, $acad_links);
        
        // Dacă nu găsește cu ACAD explicit, caută toate link-urile item-global
        if (empty($acad_links[1])) {
            preg_match_all('/<A\s+[^>]*HREF\s*=\s*["\']?([^"\'>\s]*func=item-global[^"\'>\s]*)["\']?/i', $search_response, $all_item_links);
            $acad_links = $all_item_links;
        }
        
        // Folosește primul link găsit pentru a accesa pagina item-global și a extrage barcode-ul
        if (!empty($acad_links[1][0])) {
            $link_item = html_entity_decode(trim($acad_links[1][0]));
            $link_item = preg_replace('/[<>"\']/', '', $link_item);
            
            // Normalizează linkul
            if (strpos($link_item, 'http') === 0) {
                $temp_item_url = $link_item;
            } elseif (strpos($link_item, '/F/') === 0 || strpos($link_item, 'F/') === 0) {
                $link_item = ltrim($link_item, '/');
                $temp_item_url = "http://{$ALEPH_SERVER}:{$ALEPH_PORT}/{$link_item}";
            } else {
                // Adaugă sub_library=ACAD dacă nu există
                if (strpos($link_item, 'sub_library=') === false) {
                    $separator = (strpos($link_item, '?') !== false) ? '&' : '?';
                    $link_item .= $separator . 'sub_library=ACAD';
                }
                $temp_item_url = "{$ALEPH_BASE_URL}/{$session_id}?{$link_item}";
            }
            
            // Accesează pagina item-global pentru a extrage barcode-ul
            try {
                $temp_item_html = fetch_url($temp_item_url);
                $temp_item_html = convertAlephEncoding($temp_item_html);
                
                // Extrage barcode-ul din pagina item-global (coloana "Barcod")
                // Caută pattern: <td class=td1>C003016</td> în tabelul de exemplare
                // Barcode-ul apare în ultima coloană a tabelului
                if (preg_match_all('/<td[^>]*class=["\']?td1["\']?[^>]*>([A-Z]?\d{5,}(?:-\d{1,2})?)<\/td>/i', $temp_item_html, $barcode_matches)) {
                    // Folosește ultimul barcode găsit (cel din coloana Barcod)
                    if (!empty($barcode_matches[1])) {
                        $barcode_pentru_cautare = trim(end($barcode_matches[1]));
                        $necesita_cautare_dupa_barcode = true;
                        $debug_info['barcode_din_aleph'] = $barcode_pentru_cautare;
                    }
                }
            } catch (Exception $e) {
                $debug_info['barcode_extraction_error'] = $e->getMessage();
            }
        }
    }
    
    // Dacă am găsit un barcode, folosește-l pentru căutare exactă (DOAR pentru cotă)
    if ($necesita_cautare_dupa_barcode && !empty($barcode_pentru_cautare)) {
        // Caută din nou după barcode pentru identificare exactă
        try {
            $barcode_search_url = "{$ALEPH_BASE_URL}/{$session_id}?func=find-b&request=" . urlencode($barcode_pentru_cautare) . "&find_code=BAR&adjacent=N&local_base=RAI01";
            $barcode_search_response = fetch_url($barcode_search_url);
            
            // Verifică dacă sunt rezultate
            $no_results_barcode = (
                stripos($barcode_search_response, 'Your search found no results') !== false ||
                stripos($barcode_search_response, 'Căutarea nu a avut rezultate') !== false ||
                stripos($barcode_search_response, 'No results') !== false
            );
            
            if (!$no_results_barcode) {
                // Folosește răspunsul de la căutarea după barcode
                $search_response = $barcode_search_response;
                $used_strategy = 'BAR';
                $debug_info['used_barcode_for_exact_match'] = $barcode_pentru_cautare;
                $debug_info['original_search_was_cota'] = $search_term;
            }
        } catch (Exception $e) {
            $debug_info['barcode_search_error'] = $e->getMessage();
        }
    }
}

// 3. Extrage date pentru navigare către detalii
$full_detail_url = '';
$item_url = '';

// 🔥 METODA 1: Caută linkuri direct în răspunsul de căutare (funcționează pentru cota și barcode)
// Caută linkuri către item-global sau direct în răspunsul HTML (pattern mai permisiv)
preg_match_all('/<A\s+[^>]*HREF\s*=\s*["\']?([^"\'>\s]*func=(?:item-global|direct|full-set)[^"\'>\s]*)["\']?/i', $search_response, $direct_links);
// Dacă nu găsește cu pattern-ul de mai sus, încearcă un pattern mai simplu
if (empty($direct_links[1])) {
    preg_match_all('/href\s*=\s*["\']?([^"\'>]*func=(?:item-global|direct|full-set)[^"\'>]*)["\']?/i', $search_response, $direct_links);
}
// Dacă tot nu găsește, încearcă să găsească linkuri care conțin doc_number
if (empty($direct_links[1])) {
    preg_match_all('/href\s*=\s*["\']?([^"\'>]*doc_number[^"\'>]*)["\']?/i', $search_response, $direct_links);
}
// Dacă tot nu găsește, încearcă să găsească orice link către /F/ cu session_id
if (empty($direct_links[1])) {
    preg_match_all('/href\s*=\s*["\']?(\/F\/[^"\'>\s]+)["\']?/i', $search_response, $direct_links);
}
// Dacă tot nu găsește, încearcă să găsească linkuri în format diferit (pentru barcode)
if (empty($direct_links[1])) {
    // Caută linkuri care conțin "Filială" sau "Exemplare" (din tabelul de rezultate)
    preg_match_all('/<A\s+[^>]*HREF\s*=\s*["\']?([^"\'>\s]*\/F\/[^"\'>\s]*)["\']?/i', $search_response, $direct_links);
}
if (!empty($direct_links[1])) {
    foreach ($direct_links[1] as $link_raw) {
        $link = html_entity_decode(trim($link_raw));
        
        // Curăță linkul de caractere nedorite
        $link = preg_replace('/[<>"\']/', '', $link);
        
        // Preferă linkuri cu sub_library=ACAD sau func=item-global
        if (strpos($link, 'sub_library=ACAD') !== false || strpos($link, 'func=item-global') !== false || 
            strpos($link, 'doc_number') !== false) {
            // Normalizează linkul
            if (strpos($link, 'http') === 0) {
                $item_url = $link;
            } elseif (strpos($link, '/F/') === 0 || strpos($link, 'F/') === 0) {
                // Link relativ care începe cu /F/ sau F/
                $link = ltrim($link, '/');
                $item_url = "http://{$ALEPH_SERVER}:{$ALEPH_PORT}/{$link}";
            } elseif (strpos($link, '?') === 0) {
                // Link care începe cu ?
                $item_url = "{$ALEPH_BASE_URL}/{$session_id}{$link}";
            } elseif (strpos($link, 'func=') !== false || strpos($link, 'doc_number') !== false) {
                // Link cu parametri func sau doc_number
                $item_url = "{$ALEPH_BASE_URL}/{$session_id}?{$link}";
            } else {
                // Altfel, încearcă să construiască URL
                $item_url = "{$ALEPH_BASE_URL}/{$session_id}?{$link}";
            }
            $debug_info['found_direct_link'] = $link_raw;
            break;
        }
    }
    // Dacă nu găsește cu ACAD, folosește primul link disponibil
    if (empty($item_url) && !empty($direct_links[1][0])) {
        $link = html_entity_decode(trim($direct_links[1][0]));
        $link = preg_replace('/[<>"\']/', '', $link);
        
        // ✅ NOU - Adaugă sub_library=ACAD dacă nu există deja
        if (strpos($link, 'sub_library=') === false) {
            $separator = (strpos($link, '?') !== false) ? '&' : '?';
            $link .= $separator . 'sub_library=ACAD';
        }
        
        if (strpos($link, 'http') === 0) {
            $item_url = $link;
        } elseif (strpos($link, '/F/') === 0 || strpos($link, 'F/') === 0) {
            // Link către /F/ - verifică dacă are deja parametri
            $link = ltrim($link, '/');
            if (strpos($link, '?') === false && strpos($link, 'func=') === false) {
                // Nu are parametri - adaugă func=item-global și sub_library=ACAD
                $item_url = "http://{$ALEPH_SERVER}:{$ALEPH_PORT}/{$link}?func=item-global&doc_library=RAI01&sub_library=ACAD";
            } else {
                // Are parametri - verifică dacă are sub_library
                if (strpos($link, 'sub_library=') === false) {
                    $separator = (strpos($link, '?') !== false) ? '&' : '?';
                    $link .= $separator . 'sub_library=ACAD';
                }
                $item_url = "http://{$ALEPH_SERVER}:{$ALEPH_PORT}/{$link}";
            }
        } elseif (strpos($link, '?') === 0) {
            $item_url = "{$ALEPH_BASE_URL}/{$session_id}{$link}";
        } elseif (strpos($link, 'func=') !== false || strpos($link, 'doc_number') !== false) {
            $item_url = "{$ALEPH_BASE_URL}/{$session_id}?{$link}";
        } else {
            // Link fără parametri - încearcă să construiască cu func=item-global și sub_library=ACAD
            $item_url = "{$ALEPH_BASE_URL}/{$session_id}?func=item-global&doc_library=RAI01&sub_library=ACAD&{$link}";
        }
        $debug_info['used_first_direct_link'] = $link;
    }
}

// 🔥 METODA 2: Caută set_number și set_entry (funcționează pentru barcode)
if (empty($item_url) && preg_match('/set_number=(\d+)/i', $search_response, $set_num_match) &&
    preg_match('/set_entry=(\d+)/i', $search_response, $set_entry_match)) {
    
    $set_number = $set_num_match[1];
    $set_entry = $set_entry_match[1];
    
    // Construiește URL pentru accesarea rezultatului specific
    $result_url = "{$ALEPH_BASE_URL}/{$session_id}?func=direct&doc_number={$set_entry}&local_base=RAI01";
    $debug_info['result_url'] = $result_url;
    
    try {
        // Fetch pagina rezultatului specific
        $result_html = fetch_url($result_url);
        $result_html = convertAlephEncoding($result_html);
        
        // Acum caută linkuri în pagina rezultatului
        preg_match('/<A\s+HREF=["\']?([^"\'>\s]*func=full-set-set[^"\'>\s]*)["\']?/i', $result_html, $full_match);
        $full_detail_url = isset($full_match[1]) ? html_entity_decode(trim($full_match[1])) : '';
        
        // Caută linkuri către item-global (pattern mai permisiv)
        preg_match_all('/<A\s+[^>]*HREF\s*=\s*["\']?([^"\'>\s]*func=item-global[^"\'>\s]*)["\']?/i', $result_html, $all_links);
        // Dacă nu găsește, încearcă pattern mai simplu
        if (empty($all_links[1])) {
            preg_match_all('/href\s*=\s*["\']?([^"\'>]*func=item-global[^"\'>]*)["\']?/i', $result_html, $all_links);
        }
        // Dacă tot nu găsește, caută orice link către /F/ (poate fi în tabelul de rezultate)
        if (empty($all_links[1])) {
            preg_match_all('/<A\s+[^>]*HREF\s*=\s*["\']?([^"\'>\s]*\/F\/[^"\'>\s]*)["\']?/i', $result_html, $all_links);
        }
        
        if (!empty($all_links[1])) {
            foreach ($all_links[1] as $link) {
                $link = html_entity_decode(trim($link));
                if (strpos($link, 'sub_library=ACAD') !== false) {
                    if (strpos($link, 'http') === 0) {
                        $item_url = $link;
                    } elseif (strpos($link, '/F/') === 0) {
                        $item_url = "http://{$ALEPH_SERVER}:{$ALEPH_PORT}{$link}";
                    } else {
                        $item_url = "{$ALEPH_BASE_URL}/{$session_id}?{$link}";
                    }
                    break;
                }
            }
            if (empty($item_url) && !empty($all_links[1])) {
                $link = html_entity_decode(trim($all_links[1][0]));
                // ✅ NOU - Adaugă sub_library=ACAD dacă nu există deja
                if (strpos($link, 'sub_library=') === false) {
                    $separator = (strpos($link, '?') !== false) ? '&' : '?';
                    $link .= $separator . 'sub_library=ACAD';
                }
                
                if (strpos($link, 'http') === 0) {
                    $item_url = $link;
                } elseif (strpos($link, '/F/') === 0) {
                    $item_url = "http://{$ALEPH_SERVER}:{$ALEPH_PORT}{$link}";
                } else {
                    $item_url = "{$ALEPH_BASE_URL}/{$session_id}?{$link}";
                }
            }
        }
        
        // Dacă tot nu găsește, construiește manual
        if (empty($item_url)) {
            // Caută doc_number în pagina rezultatului
            if (preg_match('/doc_number=(\d+)/i', $result_html, $doc_match)) {
                $doc_number = $doc_match[1];
                // ✅ NOU - Adaugă sub_library=ACAD pentru a filtra după Biblioteca Academiei Iași
                $item_url = "{$ALEPH_BASE_URL}/{$session_id}?func=item-global&doc_library=RAI01&doc_number={$doc_number}&sub_library=ACAD";
                $debug_info['manual_item_url'] = true;
            }
        }
    } catch (Exception $e) {
        $debug_info['result_url_error'] = $e->getMessage();
    }
}

// 🔥 METODA 3: FALLBACK - încearcă construcție directă din search response
if (empty($item_url)) {
    // Caută orice doc_number în răspuns
    if (preg_match('/doc_number=(\d+)/i', $search_response, $doc_direct)) {
        $doc_number = $doc_direct[1];
        // ✅ NOU - Adaugă sub_library=ACAD pentru a filtra după Biblioteca Academiei Iași
        $item_url = "{$ALEPH_BASE_URL}/{$session_id}?func=item-global&doc_library=RAI01&doc_number={$doc_number}&sub_library=ACAD";
        $debug_info['fallback_doc_number'] = $doc_number;
    }
}

// 🔥 METODA 4: Caută linkuri în format diferit (pentru cazuri speciale)
if (empty($item_url)) {
    // Caută linkuri care conțin session_id și func
    preg_match_all('/\/F\/[^"\'>\s]*\?[^"\'>\s]*func=(?:item-global|direct)[^"\'>\s]*/i', $search_response, $session_links);
    if (!empty($session_links[0])) {
        $link = trim($session_links[0][0]);
        if (strpos($link, 'http') !== 0) {
            $item_url = "http://{$ALEPH_SERVER}:{$ALEPH_PORT}{$link}";
        } else {
            $item_url = $link;
        }
        $debug_info['found_session_link'] = $link;
    }
}

$debug_info['full_detail_url'] = $full_detail_url;
$debug_info['item_url'] = $item_url;

if (empty($full_detail_url) && empty($item_url)) {
    return [
        'success' => false,
        'mesaj' => "Nu s-au găsit linkuri către detalii în pagina de rezultate",
        'debug' => $debug_info,
        'search_response_sample' => substr($search_response, 0, 2000)
    ];
}
        
        // Inițializare date
        $data = [
            'titlu' => '',
            'autor' => '',
            'autor_complet' => '',
            'isbn' => '',
            'anul' => '',
            'editura' => '',
            'localitate' => '',
            'cota' => '',
            'locatie' => '',
            'colectie' => '',
            'biblioteca' => '',
            'status' => '',
            'barcode' => '',
            'sectiune' => ''
        ];
        
        // ✅ CRITIC - NU extragem titlul din search_response (pagina de rezultate)
        // Titlul real trebuie extras DOAR din item_html (pagina item-global)
        // search_response conține doar text generic de navigare ("Înregistrările selectate", etc.)
        
        // 4. FETCH PAGINA COMPLETĂ (full_detail_url - dacă există)
        if (!empty($full_detail_url)) {
            try {
                $full_html = fetch_url($full_detail_url);
                $full_html = convertAlephEncoding($full_html);
                
                $dom = new DOMDocument();
                @$dom->loadHTML(mb_convert_encoding($full_html, 'HTML-ENTITIES', 'UTF-8'));
                $tds = $dom->getElementsByTagName('td');
                
                for ($i = 0; $i < $tds->length - 1; $i++) {
                    $current_td = $tds->item($i);
                    $next_td = $tds->item($i + 1);
                    
                    if ($current_td->getAttribute('class') !== 'td1' || 
                        $next_td->getAttribute('class') !== 'td1') {
                        continue;
                    }
                    
                    $label = trim($current_td->textContent);
                    $value = trim($next_td->textContent);
                    
                    if (empty($value) || $value === ' ') {
                        continue;
                    }
                    
                    // Verifică că nu este text de navigare
                    $clean_value = trim($value);
                    if (stripos($clean_value, 'Catalog general') !== false || 
                        stripos($clean_value, 'Colecţii') !== false || 
                        stripos($clean_value, 'Selectaţi') !== false) {
                        continue;
                    }
                    
                    if (stripos($label, 'ISBN') !== false && empty($data['isbn'])) {
                        if (preg_match('/[\d\-Xx]{10,}/', $value, $isbn_match)) {
                            $data['isbn'] = $isbn_match[0];
                        }
                    } 
                    else if (stripos($label, 'Autor') !== false && empty($data['autor'])) {
                        if (strlen($clean_value) > 2 && !stripos($clean_value, 'Selectaţi')) {
                            $data['autor'] = $clean_value;
                        }
                    } 
                    else if (stripos($label, 'Titlu') !== false && empty($data['titlu'])) {
                        if (strlen($clean_value) > 5 && !stripos($clean_value, '>')) {
                            $data['titlu'] = $clean_value;
                        }
                    } 
                    else if (stripos($label, 'Editură') !== false && empty($data['editura'])) {
                        $data['editura'] = $clean_value;
                    } 
                    else if (stripos($label, 'Localitate') !== false && empty($data['localitate'])) {
                        $data['localitate'] = $clean_value;
                    } 
                    else if (stripos($label, 'An') !== false && empty($data['anul']) && preg_match('/\b(19|20)\d{2}\b/', $value, $year_match)) {
                        $data['anul'] = $year_match[0];
                    }
                    // Caută cota în full_detail_url
                    else if ((stripos($label, 'Call') !== false || stripos($label, 'Cotă') !== false || 
                             stripos($label, 'Cota') !== false || stripos($label, 'Locat') !== false) && 
                            empty($data['cota']) && !empty($value)) {
                        if (preg_match('/^[A-Z]{1,3}[\s\-]?\d+([\s\-]\d+)?$/i', trim($value))) {
                            $data['cota'] = trim($value);
                            $data['locatie'] = trim($value);
                        }
                    }
                }
            } catch (Exception $e) {
                $debug_info['full_detail_url_error'] = $e->getMessage();
            }
        }
        
        // 5. FETCH PAGINA EXEMPLARE
        if (!empty($item_url)) {
            $item_html = fetch_url($item_url);
            
            // DEBUGGING - salvează răspunsul brut
            @file_put_contents('debug_aleph_raw.html', $item_html);
            
            // Detectează encoding-ul original (fără Windows-1250 - nu e recunoscut de mb_detect_encoding)
            $detected_encoding = mb_detect_encoding($item_html, ['UTF-8', 'ISO-8859-2', 'ISO-8859-1'], true);
            @file_put_contents('debug_encoding.txt', "Detected: " . ($detected_encoding ?: 'UNKNOWN') . "\n", FILE_APPEND);
            
            // Convertește
            $item_html = convertAlephEncoding($item_html);
            
            // Salvează după conversie
            @file_put_contents('debug_aleph_converted.html', $item_html);
            
            // 🔥 METODA 1: Caută titlu/autor în format HTML standard (evită text de navigare)
            // ✅ CRITIC - Exclude link-urile de navigare și header-ul înainte de a extrage titlul
            // Exclude link-uri de navigare (func=BOR-INFO, func=file&file_name=login, etc.)
            $item_html_clean = preg_replace('/<a\s+[^>]*href[^>]*(?:func=(?:BOR-INFO|file|logout|option-show|base-list|feedback|help-1|ill-request))[^>]*>.*?<\/a>/is', '', $item_html);
            $item_html_clean = preg_replace('/<A\s+[^>]*HREF[^>]*(?:func=(?:BOR-INFO|file|logout|option-show|base-list|feedback|help-1|ill-request))[^>]*>.*?<\/A>/is', '', $item_html_clean);
            
            // Exclude header-ul paginii (middlebar, etc.)
            $item_html_clean = preg_replace('/<td[^>]*class=["\']?middlebar["\'][^>]*>.*?<\/td>/is', '', $item_html_clean);
            
            // Exclude textul generic de navigare
            $item_html_clean = preg_replace('/<a[^>]*>(?:Permis de bibliotecă|Înregistrările selectate|Selectați|Sfârşitul sesiunii)<\/a>/is', '', $item_html_clean);
            $item_html_clean = preg_replace('/<A[^>]*>(?:Permis de bibliotecă|Înregistrările selectate|Selectați|Sfârşitul sesiunii)<\/A>/is', '', $item_html_clean);
            
            if (empty($data['titlu'])) {
                // Pattern 1: Author. Title : ... / ... (format standard Aleph din item-global)
                // Exemplu: "Author Uritescu, Dorin N.. Fascinaţia numelui : Studiu al creaţiei lexico-semantice şi stilistice, în relaţiile: -nume propriu-nume comun şi nume comun-nume propriu- / Dorin N. Uritescu"
                if (preg_match('/Author\s+([^\.]+)\.\.?\s+([^:]+):\s*([^\/]+)\s*\/\s*(.+?)(?:<br>|<\/|$)/is', $item_html_clean, $matches)) {
                    $autor = trim(strip_tags($matches[1]));
                    $titlu_part1 = trim(strip_tags($matches[2]));
                    $titlu_part2 = trim(strip_tags($matches[3]));
                    $titlu = $titlu_part1 . ' : ' . $titlu_part2;
                    
                    // Verifică că nu este text de navigare sau text generic
                    if (!stripos($titlu, 'Catalog general') && !stripos($titlu, 'Colecţii') && 
                        !stripos($titlu, 'exemplare') && !stripos($titlu, 'Selectaţi') &&
                        !stripos($titlu, 'BARI') && !stripos($titlu, 'catalog') &&
                        !stripos($titlu, 'Permis de bibliotecă') && !stripos($titlu, 'Permis de biblioteca') &&
                        !stripos($titlu, 'Înregistrările selectate') && !stripos($titlu, 'Inregistrarile selectate') &&
                        strlen($titlu) > 10) {
                        if (empty($data['autor'])) {
                            $data['autor'] = $autor;
                        }
                        $data['titlu'] = $titlu;
                        $data['autor_complet'] = trim(strip_tags($matches[4]));
                    }
                }
                // Pattern 1b: Author. Title / ... (format standard Aleph fără două puncte)
                elseif (preg_match('/Author\s+([^.]+)\.\s+([^\/]+)\s*\/\s*(.+?)(?:<br>|<\/|$)/is', $item_html_clean, $matches)) {
                    $autor = trim(strip_tags($matches[1]));
                    $titlu = trim(strip_tags($matches[2]));
                    // Verifică că nu este text de navigare sau text generic
                    if (!stripos($titlu, 'Catalog general') && !stripos($titlu, 'Colecţii') && 
                        !stripos($titlu, 'exemplare') && !stripos($titlu, 'Selectaţi') &&
                        !stripos($titlu, 'BARI') && !stripos($titlu, 'catalog') &&
                        !stripos($titlu, 'Permis de bibliotecă') && !stripos($titlu, 'Permis de biblioteca') &&
                        !stripos($titlu, 'Înregistrările selectate') && !stripos($titlu, 'Inregistrarile selectate') &&
                        strlen($titlu) > 10) {
                        if (empty($data['autor'])) {
                            $data['autor'] = $autor;
                        }
                        $data['titlu'] = $titlu;
                        $data['autor_complet'] = trim(strip_tags($matches[3]));
                    }
                }
                // Pattern 2: Format alternativ - Title / Author (doar dacă nu este text generic)
                elseif (preg_match('/([^\/]{20,})\s*\/\s*([^<]{5,})/i', $item_html_clean, $matches)) {
                    $potential_title = trim(strip_tags($matches[1]));
                    $potential_author = trim(strip_tags($matches[2]));
                    // Verifică că nu este text de navigare sau text generic (BARI, catalog, etc.)
                    if (strlen($potential_title) > 10 && !stripos($potential_title, 'Catalog') && 
                        !stripos($potential_title, 'Colecţii') && !stripos($potential_title, 'Selectaţi') &&
                        !stripos($potential_title, 'BARI') && !stripos($potential_title, 'catalog general') &&
                        !stripos($potential_title, 'Permis de bibliotecă') && !stripos($potential_title, 'Permis de biblioteca') &&
                        !stripos($potential_title, 'Înregistrările selectate') && !stripos($potential_title, 'Inregistrarile selectate')) {
                        $data['titlu'] = $potential_title;
                        if (strlen($potential_author) > 2 && !stripos($potential_author, 'Selectaţi')) {
                            $data['autor'] = $potential_author;
                        }
                    }
                }
                // Pattern 3: În tag-uri <pre> sau <div> cu text lung (format Aleph)
                elseif (preg_match('/<(?:pre|div)[^>]*>([^<]{30,500})<\/(?:pre|div)>/is', $item_html_clean, $matches)) {
                    $text_block = trim(strip_tags($matches[1]));
                    // Caută pattern Author. Title
                    if (preg_match('/([^\.]+)\.\s+([^\/]+)\s*\/\s*(.+)/', $text_block, $text_matches)) {
                        $autor = trim($text_matches[1]);
                        $titlu = trim($text_matches[2]);
                        // Verifică că nu este text de navigare sau text generic
                        if (strlen($titlu) > 10 && !stripos($titlu, 'Catalog') && !stripos($titlu, 'Selectaţi') &&
                            !stripos($titlu, 'BARI') && !stripos($titlu, 'catalog general') &&
                            !stripos($titlu, 'Permis de bibliotecă') && !stripos($titlu, 'Permis de biblioteca') &&
                            !stripos($titlu, 'Înregistrările selectate') && !stripos($titlu, 'Inregistrarile selectate')) {
                            $data['autor'] = $autor;
                            $data['titlu'] = $titlu;
                            $data['autor_complet'] = trim($text_matches[3]);
                        }
                    }
                }
            }
            
            // 🔥 METODA 2: Parsing DOM îmbunătățit
            $dom = new DOMDocument();
            @$dom->loadHTML(mb_convert_encoding($item_html, 'HTML-ENTITIES', 'UTF-8'));
            $tds = $dom->getElementsByTagName('td');
            
            // Parsing perechi label-value (verifică și fără class td1)
            for ($i = 0; $i < $tds->length - 1; $i++) {
                $current_td = $tds->item($i);
                $next_td = $tds->item($i + 1);
                $label = trim($current_td->textContent);
                $value = trim($next_td->textContent);
                
                // Extrage date din perechi label-value
                if (stripos($label, 'Title') !== false || stripos($label, 'Titlu') !== false) {
                    if (empty($data['titlu']) && !empty($value)) {
                        // Verifică că nu este text de navigare
                        $clean_value = trim($value);
                        // Verifică că nu este text de navigare sau text generic
                        if (!stripos($clean_value, 'Catalog general') && !stripos($clean_value, 'Colecţii') && 
                            !stripos($clean_value, 'exemplare') && !stripos($clean_value, 'Selectaţi') &&
                            !stripos($clean_value, 'BARI') && !stripos($clean_value, 'catalog general') &&
                            !stripos($clean_value, 'Permis de bibliotecă') && !stripos($clean_value, 'Permis de biblioteca') &&
                            !stripos($clean_value, 'Înregistrările selectate') && !stripos($clean_value, 'Inregistrarile selectate') &&
                            !stripos($clean_value, '>') && strlen($clean_value) > 10) {
                            $data['titlu'] = $clean_value;
                        }
                    }
                }
                if (stripos($label, 'Author') !== false || stripos($label, 'Autor') !== false) {
                    if (empty($data['autor']) && !empty($value)) {
                        $clean_value = trim($value);
                        if (!stripos($clean_value, 'Selectaţi') && strlen($clean_value) > 2) {
                            $data['autor'] = $clean_value;
                        }
                    }
                }
            }
            
            // Parsing alternativ: caută în toate TD-urile pentru text lung (posibil titlu)
            if (empty($data['titlu'])) {
                for ($i = 0; $i < $tds->length; $i++) {
                    $td = $tds->item($i);
                    $text = trim($td->textContent);
                    
                    // ✅ NOU - Exclude explicit textul din navigare
                    $text_exclus = [
                        'Sfârşitul sesiunii', 'Sfârșitul sesiunii', 'End of session',
                        'Selectați anul', 'Selectaţi anul', 'Select the year',
                        'Conectaţi-vă', 'Log in', 'Sesiune expirată',
                        'Permis de bibliotecă', 'Permis de biblioteca', 'Library permit',
                        'Biblioteca Academiei Iaşi', 'Biblioteca Academiei Iasi',
                        'Înregistrările selectate', 'Inregistrarile selectate', 'Selected records'
                    ];
                    
                    $is_text_navigare = false;
                    foreach ($text_exclus as $exclus) {
                        if (stripos($text, $exclus) !== false) {
                            $is_text_navigare = true;
                            break;
                        }
                    }
                    
                    if ($is_text_navigare) {
                        continue;
                    }
                    
                    // Caută text lung care ar putea fi titlu (20-500 caractere, fără text de navigare)
                    if (strlen($text) >= 20 && strlen($text) <= 500 && 
                        !stripos($text, 'Catalog') && !stripos($text, 'Colecţii') && 
                        !stripos($text, 'Selectaţi') && !stripos($text, 'exemplare') &&
                        !stripos($text, 'BARI') && !stripos($text, 'catalog general') &&
                        !stripos($text, 'Permis de bibliotecă') && !stripos($text, 'Permis de biblioteca') &&
                        !stripos($text, 'Înregistrările selectate') && !stripos($text, 'Inregistrarile selectate') &&
                        !preg_match('/^[A-Z]{1,3}[\s\-]?\d+([\s\-]\d+)?$/i', $text) && // nu este cota
                        !preg_match('/^([A-Z]{1,3})?\d{5,}(-\d{1,2})?$/i', $text)) { // nu este barcode
                        // Verifică dacă conține pattern de titlu (mai multe cuvinte)
                        if (preg_match('/\b\w+\b.*\b\w+\b.*\b\w+\b/', $text)) {
                            $data['titlu'] = $text;
                            break;
                        }
                    }
                }
            }
            
            // ✅ NOU - Caută titlul în tabelul de rezultate (format Aleph standard)
            if (empty($data['titlu'])) {
                // Caută pattern: "Author. Title / ..." în formatul tabelului Aleph
                if (preg_match('/<td[^>]*>([^<]+(?:ed\.|trad\.|,)[^<]*)\.\s+([^<]+(?:"[^"]+"|Corespondenţa|Corespondenta)[^<]*)<\/td>/is', $item_html, $matches)) {
                    $autor_potential = trim(strip_tags($matches[1]));
                    $titlu_potential = trim(strip_tags($matches[2]));
                    
                    // Verifică că nu este text de navigare
                    if (strlen($titlu_potential) > 15 && 
                        !stripos($titlu_potential, 'Sfârşitul') && 
                        !stripos($titlu_potential, 'Selectați') &&
                        !stripos($titlu_potential, 'Catalog')) {
                        $data['titlu'] = $titlu_potential;
                        if (strlen($autor_potential) > 2) {
                            $data['autor'] = $autor_potential;
                        }
                    }
                }
            }
            
            // Parsing individual TD-uri pentru cota, barcode, etc.
            for ($i = 0; $i < $tds->length; $i++) {
                $td = $tds->item($i);
                $text = trim($td->textContent);
                
                // 🔥 COTĂ - pattern mai permisiv (acceptă I-14156, I 14156, I14156, etc.)
                // Caută în toate TD-urile, nu doar în primul
                if (empty($data['cota']) && 
                    preg_match('/^[A-Z]{1,3}[\s\-]?\d+([\s\-]\d+)?$/i', $text)) {
                    $data['cota'] = $text;
                    $data['locatie'] = $text;
                }
                // Caută și variante cu spații sau format diferit
                if (empty($data['cota']) && 
                    preg_match('/\b([A-Z]{1,3}[\s\-]?\d+[\s\-]?\d*)\b/i', $text, $cota_match)) {
                    $potential_cota = trim($cota_match[1]);
                    if (preg_match('/^[A-Z]{1,3}[\s\-]?\d+([\s\-]\d+)?$/i', $potential_cota)) {
                        $data['cota'] = $potential_cota;
                        $data['locatie'] = $potential_cota;
                    }
                }
                
                // Colecție
                if (empty($data['colectie']) && 
                    (stripos($text, 'depozit') !== false || stripos($text, 'Cărți') !== false || 
                     stripos($text, 'sala de lectură') !== false || stripos($text, 'Carte') !== false)) {
                    if (!stripos($text, 'Bibliotec') && strlen($text) < 100) {
                        $data['colectie'] = $text;
                        $data['sectiune'] = $text;
                    }
                }
                
                // Bibliotecă
                if (empty($data['biblioteca']) && 
                    stripos($text, 'Biblioteca Academiei') !== false &&
                    stripos($text, 'Toate') === false) {
                    $data['biblioteca'] = $text;
                }
                
                // Status
                if (empty($data['status']) && 
                    (stripos($text, 'Pe raft') !== false || 
                     stripos($text, 'Pentru împrumut') !== false ||
                     stripos($text, 'Împrumutat') !== false ||
                     stripos($text, 'Doar pentru SL') !== false)) {
                    $data['status'] = $text;
                }
                
                // 🔥 BARCODE - pattern mai permisiv
                if (empty($data['barcode']) && 
                    preg_match('/^([A-Z]{1,3})?\d{5,10}(-\d{1,2})?$/i', $text)) {
                    $data['barcode'] = $text;
                }
            }
        }
        
        // Curăță și normalizează toate câmpurile text
        foreach ($data as $key => $value) {
            if (is_string($value) && !empty($value)) {
                // Fix encoding dacă mai sunt probleme
                $data[$key] = convertAlephEncoding($value);
                // Curăță spații multiple și caractere invizibile
                $data[$key] = preg_replace('/\s+/', ' ', trim($data[$key]));
            }
        }
        
        // Verifică dacă titlul este un mesaj de eroare/sesiune expirată
        $titlu = trim($data['titlu'] ?? '');
        $mesaje_eroare = [
            'Sfârşitul sesiunii',
            'Sfârșitul sesiunii',
            'End of session',
            'Session ended',
            'Sesiune expirată',
            'Session expired'
        ];
        
        foreach ($mesaje_eroare as $mesaj_eroare) {
            if (stripos($titlu, $mesaj_eroare) !== false) {
                return [
                    'success' => false,
                    'mesaj' => "Nu există această carte în baza de date Aleph",
                    'debug' => $debug_info,
                    'data_partiala' => $data
                ];
            }
        }
        
        // Verifică date minime
        if (empty($data['titlu']) || strlen($titlu) < 3) {
            return [
                'success' => false,
                'mesaj' => "Nu există această carte în baza de date Aleph",
                'debug' => $debug_info,
                'data_partiala' => $data
            ];
        }
        
        return [
            'success' => true,
            'data' => $data,
            'debug' => $debug_info
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'mesaj' => $e->getMessage(),
            'debug' => $debug_info
        ];
    }
}

// ==========================================
// FUNCȚII WRAPPER - folosesc strategia AUTO
// ==========================================
function cautaCarteInAlephDupaBarcode($barcode) {
    return cautaCarteInAleph($barcode, 'AUTO');
}

function cautaCarteInAlephDupaCota($cota) {
    return cautaCarteInAleph($cota, 'AUTO');
}

// ==========================================
// API ENDPOINT (când se apelează direct)
// ==========================================
if (isset($_GET['cota']) || isset($_GET['barcode'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    if (isset($_GET['barcode'])) {
        $result = cautaCarteInAlephDupaBarcode($_GET['barcode']);
    } else {
        $result = cautaCarteInAlephDupaCota($_GET['cota']);
    }
    
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
?>