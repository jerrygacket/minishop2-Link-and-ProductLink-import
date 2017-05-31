<?php

define('MODX_API_MODE', true);
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/config/config.inc.php';
require_once MODX_BASE_PATH . 'index.php';

if (XPDO_CLI_MODE) {
        $file = @$argv[1];
        $fields = @$argv[2];
        $update = (bool) !empty($argv[3]);
        $key = @$argv[4];
        $is_debug = (bool) !empty($argv[5]);
        $delimeter = @$argv[6];
}
else {
        $file = @$_REQUEST['file'];
        $fields = @$_REQUEST['fields'];
        $update = (bool) !empty($_REQUEST['update']);
        $key = @$_REQUEST['key'];
        $is_debug = (bool) !empty($_REQUEST['debug']);
        $delimeter = @$_REQUEST['delimeter'];
}

// Load main services
$modx->setLogTarget(XPDO_CLI_MODE ? 'ECHO' : 'HTML');
$modx->setLogLevel($is_debug ? modX::LOG_LEVEL_INFO : modX::LOG_LEVEL_ERROR);
$modx->getService('error','error.modError');
$modx->lexicon->load('minishop2:default');
$modx->lexicon->load('minishop2:manager');

// Time limit
set_time_limit(600);
$tmp = 'Trying to set time limit = 600 sec: ';
$tmp .= ini_get('max_execution_time') == 600 ? 'done' : 'error';
$modx->log(modX::LOG_LEVEL_INFO,  $tmp);

// Check required options
if (empty($fields)) {
        $modx->log(modX::LOG_LEVEL_ERROR, 'You must specify the parameter "fields". It needed for parse of your file.');
        exit;
}
if (empty($key)) {
        $modx->log(modX::LOG_LEVEL_ERROR, 'You must specify the parameter "key". It needed for check for duplicates.');
        exit;
}
$keys = array_map('trim', explode(',', strtolower($fields)));
$tv_enabled = false;
foreach ($keys as $v) {
        if (preg_match('/^tv(\d)$/', $v)) {
                $tv_enabled = true;
                break;
        }
}
if (empty($delimeter)) {$delimeter = ';';}

// Check file
if (empty($file)) {
        $error = 'You must specify an file in the ';
        $error .= XPDO_CLI_MODE ? 'first parameter of console call' : '$_GET["file"] parameter';
        $error .= '!';
        $modx->log(modX::LOG_LEVEL_ERROR, $error);
        exit;
}
elseif (!preg_match('/\.csv$/i', $file)) {
        $modx->log(modX::LOG_LEVEL_ERROR, 'Wrong file extension. File must be an *.csv.');
        exit;
}

$file = str_replace('//', '/', MODX_BASE_PATH . $file);
if (!file_exists($file)) {
        $modx->log(modX::LOG_LEVEL_ERROR, 'File not found at '.$file.'.');
        exit;
}

// Load links for Duplicate check
$q = $modx->newQuery('msLink');
$q->select('id,name,type,description');
if ($q->prepare() && $q->stmt->execute()) {
	$ids = $q->stmt->fetchAll(PDO::FETCH_ASSOC);
}
//print_r($ids);

// Import!
$handle = fopen($file, "r");
$rows = $created = $updated = 0;
while (($csv = fgetcsv($handle, 0, $delimeter)) !== false) {
        $rows ++;
        $data = $gallery = array();
        $modx->error->reset();
        $modx->log(modX::LOG_LEVEL_INFO, "Raw data for import: \n".print_r($csv,1));
        foreach ($keys as $k => $v) {
                $data[$v] = $csv[$k];
        }

        $modx->log(modX::LOG_LEVEL_INFO, "Array with importing data: \n".print_r($data, 1));
		
		$action = 'create';
		//Duplicate check
		foreach ($ids as $value) {
			if ($value[$key] == $data[$key]) {
				$action = 'update';
				$data['id'] = $value['id'];
				//print_r($value);
				break;
			}
			//echo implode(";",$value)." --- ".$data[$key].PHP_EOL;
		}
		//echo $action.PHP_EOL;
		//print_r($data);
		       
        // Create or update resource
        /** @var modProcessorResponse $response */
		// Массив опций для метода runProcessor
		$otherProps = array(
			// Здесь указываем где лежат наши процессоры
			'processors_path' => $modx->getOption('core_path') . 'components/minishop2/processors/'
		);
		// Запускаем
		$response = $modx->runProcessor('mgr/settings/link/'.$action, $data, $otherProps);
		
        if ($response->isError()) {
                $modx->log(modX::LOG_LEVEL_ERROR, "Error on $action: \n". print_r($response->getAllErrors(), 1));
        }
        else {
                if ($action == 'update') {$updated ++;}
                else {$created ++;}

                $resource = $response->getObject();
                $modx->log(modX::LOG_LEVEL_INFO, "Successful $action: \n". print_r($resource, 1));
        }

        if ($is_debug) {
                $modx->log(modX::LOG_LEVEL_INFO, 'You in debug mode, so we process only 1 row. Time: '.number_format(microtime(true) - $modx->startTime, 7) . " s");
                exit;
        }
}
fclose($handle);

if (!XPDO_CLI_MODE) {echo '<pre>';}
echo "\nImport complete in ".number_format(microtime(true) - $modx->startTime, 7) . " s\n";
echo "\nTotal rows:     $rows\n";
echo "Created:  $created\n";
echo "Updated:  $updated\n";
if (!XPDO_CLI_MODE) {echo '</pre>';}
