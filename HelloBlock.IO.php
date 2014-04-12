<?php
require_once('pest/PestJSON.php');
class HelloBlockIO {
    // Uses https://helloblock.io/docs/ref API
    // Uses a slightly forked version of https://github.com/educoder/pest library
    protected $rest;
    protected $testnet;
    public function __construct($testnet=true) {
	$this->testnet=$testnet;
	$this->rest = new PestJSON('https://'.($testnet ? 'test' : 'main').'net.helloblock.io/v1/');
    }
    
    public function getAddress($address){
	return $this->processResult($this->rest->get('addresses/'.$address), 'address');
    }
    
    public function getAddresses(array $addresses){
	return $this->processResult($this->rest->get('addresses', array('addresses'=>$addresses)), 'addresses');
    }
    
    public function getAddressUnspents($address, $limit=10, $offset=0){
	return $this->processResult($this->rest->get('addresses/'.$address.'/unspents', array('limit'=>$limit, 'offset'=>$offset)), 'unspents');
    }
    
    public function getAddressesUnspents(array $addresses, $limit=10, $offset=0){
	return $this->processResult($this->rest->get('addresses/unspents', array('addresses'=>$addresses, 'limit'=>$limit, 'offset'=>$offset)), 'unspents');
    }
    
    public function getAddressTransactions($address, $limit=10, $offset=0){
	return $this->processResult($this->rest->get('addresses/'.$address.'/transactions', array('limit'=>$limit, 'offset'=>$offset)), 'transactions');
    }
    
    public function getAddressesTransactions(array $addresses, $limit=10, $offset=0){
	return $this->processResult($this->rest->get('addresses/transactions', array('addresses'=>$addresses, 'limit'=>$limit, 'offset'=>$offset)), 'transactions');
    }
    
    public function getTransaction($txHash){
	return $this->processResult($this->rest->get('transactions/'.$txHash), 'transaction');
    }
    
    public function getTransactions($txHashs){
	return $this->processResult($this->rest->get('transactions', array('txHashes'=>$txHashs), 'transactions'));
    }
    
    public function getLatestTransactions($limit=10, $offset=0){
	return $this->processResult($this->rest->get('transactions/latest', array('limit'=>$limit, 'offset'=>$offset)), 'transactions');
    }
    
    public function propagateTransaction($rawTxHex){
	return $this->processResult($this->rest->post('transactions', array('rawTxHex'=>$rawTxHex)), 'transaction');
    }
    
    public function getWallet($addresses, $txs=false, $unspents=false, $limit=10, $offset=0){
	$expected=array('summary', 'addresses');
	$params=array('addresses'=>$addresses, 'limit'=>$limit=10, 'offset'=>$offset=0);
	if($txs){$expected[]='transactions';$params['transactions']='true';}
	if($unspents){$expected[]='unspents';$params['unspents']='true';}
	
	return $this->processResult($this->rest->get('wallet', $params), $expected);
    }
    
    public function faucetWithdrawal($toAddress, $amount){
	$amount = intval($amount);
	if(!$this->testnet){
	    throw new Exception('Faucet is only available on testnet');
	}
	if($amount>1000000){
	    throw new Exception('Max faucet withdrawal is 1,000,000 satoshis, '.$amount.' required');
	}
	return $this->processResult($this->rest->post('faucet/withdrawal', array('toAddress'=>$toAddress,'amount'=>$amount)), array('fromAddress', 'txHash'));
    }
    
    public function getFaucetKeys($type=1){
	$type=intval($type);
	if($type<1||$type>3){
	    throw new Exception('Invalid type');
	}
	return $this->processResult($this->rest->get('faucet', array('type'=>$type)), array('privateKeyWIF', 'privateKeyHex', 'address', 'hash160', 'unspents'));
    }
    
    protected function processResult($result, $expected=null, &$meta=null){
	if(!isset($result['status']) || $result['status']!='success'){
	    $message = isset($result['message']) ? $result['message'] : $this->rest->getLastJsonErrorMessage();
	    throw new Exception($message);
	} else {
	    if($expected!==null){
		if(!isset($result['data'])){
		    throw new Exception('Expected data aren\'t here');
		}
		$meta= isset($result['meta']) ? $result['meta'] : array();
		if(is_array($expected)){
		    foreach($expected as $key){
			if(!isset($result['data'][$key])){
			    throw new Exception('Expected '.$key.' aren\'t here');
			}
		    }
		    return $result['data'];
		} else {
		    if(!isset($result['data'][$expected])){
			throw new Exception('Expected '.$expected.' aren\'t here');
		    }
		    return $result['data'][$expected];
		}
	    } else {
		return true;
	    }
	}
    }
}
?>