<?php

class GS_CONTACT{

	protected $strApiKey;
	protected $strApiUrl;
	
	protected $strUsername;
	protected $strPassword;
	
	protected $strErrorMessage;
	
	public function __construct($strApiKey, $strApiUrl){
		$this->strApiKey = $strApiKey;
		$this->strApiUrl = $strApiUrl;
		session_start();
	}
	
	public function setUsernamePassword($strUsername, $strPassword){
		if (strlen(trim($strUsername)) < 4 ) throw new Exception('Benutzername muss mindestens 4 Zeichen lang sein');
		if (strlen(trim($strPassword)) < 4 ) throw new Exception('Passwort muss mindestens 4 Zeichen lang sein');
		$this->strUsername = $strUsername;
		$this->strPassword = $strPassword;
	}
	
	public function dispatchRequest(){

		$strRequest = $_GET['p'];

		if ($strRequest != 'login' && $strRequest != 'list' && $strRequest != 'details' && $strRequest != 'logout') $strRequest = 'login';
		if ($strRequest == 'logout') $this->actionLogout();
		if (false == $this->isUserAuthenticated()) $strRequest = 'login';
		if ($strRequest == 'login' && $this->isUserAuthenticated()) $strRequest = 'list';
		if ($strRequest == 'details' && isset($_GET['id']) == false) $strRequest = 'list';
		
		switch ($strRequest) {
		    case 'list':
		    	$this->actionOverview();
		        break;
		    case 'details':
		        $this->actionDetails($_GET['id']);
		        break;
		    default:
		    	$this->actionLogin();
		        break;
		}
	}


	////////////////////////////////////////////////
	// controller

	protected function actionLogin(){
		if (isset($_POST['user'])){
			if ($_POST['user'] == $this->strUsername && $_POST['pass'] == $this->strPassword){
				$_SESSION['gscontact'] = 1;
				header('Location:index.php');
			} else {
				$this->setError('Login fehlgeschlagen!');
			}
		}
		$this->viewShowLogin();
	}
	
	protected function actionLogout(){
		unset($_SESSION['gscontact']);
	}
	
	protected function actionOverview(){
		$strSearchstring='';
		if (isset($_POST['searchfor'])) $strSearchstring = trim($_POST['searchfor']);
		$arrCustomers = $this->modelGetCustomersList($strSearchstring);
		$this->viewShowOverview($arrCustomers);
	}
	
	protected function actionDetails($intCustomerId){
		$arrCustomer = $this->modelGetCustomerDetails($intCustomerId);
		$this->viewShowDetails($arrCustomer);
	}
	
	
	////////////////////////////////////////////////
	// helper	
	
	
	protected function isUserAuthenticated(){
		if (isset($_SESSION['gscontact'])) return true;
		return false;
	}
	
	protected function setError($strMessage){
		$this->strErrorMessage = $strMessage;
	}
	
	protected function getError(){
		return $this->strErrorMessage;
	}
	
	
	////////////////////////////////////////////////
	// model

	
	protected function modelGetCustomersList($strSearchString=''){
		ini_set("soap.wsdl_cache_enabled", "0");
		$objClient = new soapclient($this->strApiUrl); 
		if ($strSearchString != '') $arrSOAPFilter[] = array('field'=>'company', 'operator'=>'like', 'value'=>$strSearchString);
		$arrSOAPSort = array('field'=>'company', 'direction'=>'asc');
		$customers = $objClient->getCustomers($this->strApiKey, $arrSOAPFilter,$arrSOAPSort,999999,0);
		if ($customers['status']->code == 0){
			foreach ((array)$customers['result'] as $key => $value){
				$arrSort[$value->id] = strtolower($value->companylabel);
				$arrReturnTmp[$value->id] = $value->companylabel;
			}
			if (is_array($arrSort)) asort($arrSort);
			foreach ((array)$arrSort as $key => $value) $arrReturn[$key] = $arrReturnTmp[$key];
			return $arrReturn;
		}
		$this->setError($customers['status']->message);
		return false;
	}
	
	protected function modelGetCustomerDetails($intCustomerId){
		ini_set("soap.wsdl_cache_enabled", "0");
		$objCLient = new soapclient($this->strApiUrl); 
		$customer = $objCLient->getCustomer($this->strApiKey, $intCustomerId);
			if ($customer['status']->code == 0){
			return $customer['result'];
		}
		$this->setError($customer['status']->message);
		return false;		
	}

	
	////////////////////////////////////////////////
	// views
	
	
	protected function viewShowLogin($strError=''){
		$this->templateHeader();
		$this->templateLogin($strError);
		$this->templateFooter(false);
	}
	
	protected function viewShowOverview($arrCustomers){
		$this->templateHeader();
		$this->templateOverview($arrCustomers);
		$this->templateFooter();
	}
	
	protected function viewShowDetails($arrCustomer){
		$this->templateHeader();
		$this->templateDetails($arrCustomer);
		$this->templateFooter();
	}
		
	
	////////////////////////////////////////////////
	// templates
	
	
	protected function templateHeader(){
		?>
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
		<html xmlns="http://www.w3.org/1999/xhtml">
		<head>
			<meta content="yes" name="apple-mobile-web-app-capable" />
			<meta content="index,follow" name="robots" />
			<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
			<link href="pics/homescreen.gif" rel="apple-touch-icon" />
			<meta content="minimum-scale=1.0, width=device-width, maximum-scale=0.6667, user-scalable=no" name="viewport" />
			<link href="css/style.css" rel="stylesheet" media="screen" type="text/css" />
			<script src="javascript/functions.js" type="text/javascript"></script>
			<title>Kontaktliste</title>
			<!--<link href="pics/startup.png" rel="apple-touch-startup-image" />-->
		</head>
			
		<?php
	}
	
	protected function templateBlockError(){
		if ($this->getError() != ''){
			echo '<ul class="pageitem">';
			echo '<li class="textbox"><span class="header" style="color:red">Fehler</span><p>'.$this->getError().'</p></li>';
			echo '</ul>';
		}
	}
	
	protected function templateFooter($booShowLogout=true){
		echo '<div id="footer">';
			if ($booShowLogout) echo '<a href="index.php?p=logout">Ausloggen</a> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
			echo '<a href="http://www.gsales.de">g*Sales API</a> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
			echo '<a href="http://iwebkit.net">Erstellt mit iWebKit</a>';
		echo '</div>';
		echo '</body>';
		echo '</html>';
	}
	
	protected function templateLogin(){
		?>
		<body>
		<div id="topbar" class="transparent">
			<div id="title">Login</div>
		</div>		
		<div id="content">

			<?php $this->templateBlockError(); ?>
		
			<form action="index.php" method="POST">
				
				<ul class="pageitem">
					<li class="bigfield"><input placeholder="Benutzername" type="text" name="user" value="<? echo $_POST['user'] ?>" /></li>
					<li class="bigfield"><input placeholder="Passwort" type="password" name="pass" /></li>
				</ul>
				<ul class="pageitem">
					<li class="button"><input name="Submit input" type="submit" value="Einloggen" /></li>
				</ul>
			</form>
		</div>		
		<?php
	}
	
	protected function templateOverview($arrCustomers){
		?>
		<body class="list">
		<div id="topbar" class="transparent">
			<div id="title">Kundenliste</div>
		</div>

		<div class="searchbox">
			<form action="index.php?p=list" method="POST">
				<fieldset><input id="search" name="searchfor" placeholder="Firmennamen durchsuchen" value="<?php echo $_POST['searchfor']; ?>" type="text" />
				<input id="submit" type="hidden" /></fieldset>
			</form>
		</div>		
		
		<div id="content">
		
			<?php $this->templateBlockError(); ?>		
		
			<?php
			
			echo '<ul>';
			if (is_array($arrCustomers)){
				foreach ($arrCustomers as $key => $value){
					$strLetter = strtoupper(substr($value,0,1));
					if ($strLetter == '' || intval($strLetter)) $strLetter = '0-9';
					if ($strLetter != $tmpLetter) echo '<li class="title">'.strtoupper($strLetter).'</li>';
					echo '<li><a href="index.php?p=details&id='.$key.'"><span class="name">'.$value.'</span><span class="arrow"></span></a></li>';
					$tmpLetter = $strLetter;
				}
			}
			echo '</ul>';
			?>
		</div>		
			
		<?php
	}
	
	protected function templateDetails($arrDetails){
		$strCompanyLabel = $arrDetails->companylabel;
		?>
		<body>

		<div id="topbar"  class="transparent">
			<div id="leftnav"><a href="index.php?p=list">Liste</a></div>
			<div id="title"><?php echo $strCompanyLabel ?></div>
		</div>
		
		<div id="content">

			<?php
			
			$this->templateBlockError();
			
			if (trim($arrDetails->title.$arrDetails->firstname.$arrDetails->lastname) != ''){
				echo '<span class="graytitle">Ansprechpartner</span>';
				echo '<ul class="pageitem">';
					echo '<li class="textbox">'.trim($arrDetails->title .' '. $arrDetails->firstname .' '.$arrDetails->lastname).'</li>';
				echo '</ul>';
			}
			
			if (trim($arrDetails->cellular.$arrDetails->email.$arrDetails->phone.$arrDetails->fax.$arrDetails->homepage) != ''){
				echo '<span class="graytitle">Kontaktdaten</span>';
				echo '<ul class="pageitem">';
					if (trim($arrDetails->cellular) != '') echo '<li class="textbox"><strong>Mobil</strong> <a class="noeffect" href="tel:'.$arrDetails->cellular.'">'.$arrDetails->cellular.'</a> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <a href="sms:'.$arrDetails->cellular.'">SMS</a></li>';
					if (trim($arrDetails->email) != '')echo '<li class="textbox"><strong>E-Mail</strong> <a class="noeffect" href="mailto:'.$arrDetails->email.'">'.$arrDetails->email.'</a></li>';
					if (trim($arrDetails->phone) != '')echo '<li class="textbox"><strong>Telefon</strong> <a class="noeffect" href="tel:'.$arrDetails->phone.'">'.$arrDetails->phone.'</a></li>';
					if (trim($arrDetails->fax) != '')echo '<li class="textbox"><strong>Fax</strong> '.$arrDetails->fax.'</li>';
					if (trim($arrDetails->homepage) != '')echo '<li class="textbox"><strong>Web</strong> <a href="'.$arrDetails->homepage.'">'.$arrDetails->homepage.'</a></li>';
				echo '</ul>';
			}			
			
			if (trim($arrDetails->company.$arrDetails->address.$arrDetails->zip.$arrDetails->city) != ''){
				echo '<span class="graytitle">Anschrift</span>';
				echo '<ul class="pageitem">';
					echo '<li class="textbox">';
						if (trim($arrDetails->company) != '') echo $arrDetails->company.'<br />';
						
						$strAddressString = trim ($arrDetails->address. ' ' .$arrDetails->zip . ' ' . $arrDetails->city);
						
						if ($strAddressString != ''){
							echo '<a class="noeffect" href="http://maps.google.com/maps?q='.urlencode($strAddressString).'">';
								if (trim($arrDetails->address) != '')  echo $arrDetails->address . '<br />';
								echo trim($arrDetails->zip . ' ' . $arrDetails->city);
							echo '</a>';
						}
						
					echo '</li>';
				echo '</ul>';
			}			
			?>
			
		</div>		
			
		<?php		
	}
	
}