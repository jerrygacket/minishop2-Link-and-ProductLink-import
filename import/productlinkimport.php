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

//Delete ALL Product links
$q = $modx->newQuery('msProductLink');
$q->command('DELETE');
$q->prepare();
$q->stmt->execute();

// Import!
$handle = fopen($file, "r");
$rows = $created = 0;
while (($csv = fgetcsv($handle, 0, $delimeter)) !== false) {
        $rows ++;
        $data = array();
        $modx->error->reset();
        $modx->log(modX::LOG_LEVEL_INFO, "Raw data for import: \n".print_r($csv,1));
        
        //From raw data to structure
        foreach ($keys as $k => $v) {
                $data[$v] = $csv[$k];
        }

		//Load article related product id`s
		$goods = explode(",",$data['goods']);
		$goods_id = array();
		foreach ($goods as $v) {
			$q = $modx->newQuery('msProductData',array('msProductData.article' => $v));
			$q->select('msProductData.id');
			if ($q->prepare() && $q->stmt->execute()) {
				$ids = $q->stmt->fetchAll(PDO::FETCH_ASSOC);
				$goods_id[] = $ids[0]['id'];
			}
		}
		//print_r($data[$key]);
		//print_r($goods_id);
		
        // Create or update link
        /** @var modProcessorResponse $response */
		// Массив опций для метода runProcessor
		$otherProps = array(
			// Здесь указываем где лежат наши процессоры
			'processors_path' => $modx->getOption('core_path') . 'components/minishop2/processors/'
		);
		
		$link_data = array();
		$link_data['link'] = $data[$key];
		$link_data['master'] = array_shift($goods_id); //первый продукт делаем мастером. для типа многие-ко-многим это все равно. для других читай методичку.
		foreach ($goods_id as $value1) {
			$link_data['slave'] = $value1;
			$response = $modx->runProcessor('mgr/product/productlink/'.'create', $link_data, $otherProps);
	
			if ($response->isError()) {
					$modx->log(modX::LOG_LEVEL_ERROR, "Error on $action: \n". print_r($response->getAllErrors(), 1));
			}
			else {
				$created = $created + count($goods_id)+1;
				$resource = $response->getObject();
				$modx->log(modX::LOG_LEVEL_INFO, "Successful $action: \n". print_r($resource, 1));
			}
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
echo "Created product links:  $created\n";
if (!XPDO_CLI_MODE) {echo '</pre>';}
