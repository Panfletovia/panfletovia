<?php

App::uses('ExceptionRenderer', 'Error');

class AppExceptionRenderer extends ExceptionRenderer {

	public function __construct(Exception $exception) {
		$this->controller = $this->_getController($exception);

		if (method_exists($this->controller, 'apperror')) {
			return $this->controller->appError($exception);
		}
		$method = $template = Inflector::variable(str_replace('Exception', '', get_class($exception)));
		$code = $exception->getCode();

		$methodExists = method_exists($this, $method);

		if ($exception instanceof CakeException && !$methodExists) {
			$method = '_cakeError';
			if (empty($template) || $template === 'internalError') {
				$template = 'error500';
			}
		} elseif ($exception instanceof PDOException) {
			$method = 'pdoError';
			$template = 'pdo_error';
			$code = 500;
		} elseif (!$methodExists) {
			$method = 'error500';
			if ($code >= 400 && $code < 500) {
				$method = 'error400';
			}
		}

		if ($method === '_cakeError') {
			$method = 'error400';
		}
		if ($code == 500) {
			$method = 'error500';
		}
		$this->template = $template;
		$this->method = $method;
		$this->error = $exception;
	}

	protected function _cakeError(CakeException $error) {
		$url = $this->controller->request->here();
		$code = ($error->getCode() >= 400 && $error->getCode() < 506) ? $error->getCode() : 500;
		$this->controller->response->statusCode($code);
		$this->controller->set($this->getData($error->getMessage(), $error));
		$this->controller->set($error->getAttributes());
		$this->_outputMessage($this->template);
	}

    
	public function error400($error) {
		$message = $error->getMessage();
		
		$url = $this->controller->request->here();
		$this->controller->response->statusCode($error->getCode());
		$data = $this->getData($message, $error);
		$this->controller->set($data);
		
		unset($data['_serialize']);
		$this->outputError($error, $data);
		
		$this->_outputMessage('error400');
		
	}

	public function error500($error) {
		$message = $error->getMessage();
		
		$code = ($error->getCode() > 500 && $error->getCode() < 506) ? $error->getCode() : 500;
		$this->controller->response->statusCode($code);
	
		$data = $this->getData($message, $error);
		$this->controller->set($data);
		
		unset($data['_serialize']);
		$this->outputError($error, $data);
		
		$this->_outputMessage('error500');
	
	}
	
	public function pdoError(PDOException $error) {
		$message = $error->getMessage();
		if (!Configure::read('debug')) {
			$message = __d('cake', 'An Internal Error Has Occurred.');
		}
		$code = 500;
		$this->controller->response->statusCode($code);
	
		$data = $this->getData($message, $error);
		$this->controller->set($data);
	
		unset($data['_serialize']);
		$this->outputError($error, $data);
		
		$this->_outputMessage('error500');
		
		
	}
	
	private function getData($message, $error) {
		$url = $this->controller->request->here();
		$params = array();
		if (method_exists($error, 'getParams')) {
			$params = $error->getParams();
		}

		$data = array(
			'name' => h($message),
			'message' => h($url),
			'error' => $error,
			'_serialize' => array_merge(array('name', 'message'), array_keys($params))
		);
		foreach ($params as $key => $value) {
			$data[$key] = $value;
		}
		return $data;
	}

	private function outputError($error, $output) {
		$this->exceptionEquipamento = CakeSession::read("exception.equipamento");
		$this->exceptionComunicacaoId = CakeSession::read("exception.comunicacaoId");
		
		CakeSession::write("exception.equipamento", null);
		CakeSession::write("exception.comunicacaoId", null);
	
		$message = $error->getMessage();
		$code = (int)$error->getCode();	
		if ($code > 599) {
			$code = 500;
		}
		if ($this->exceptionComunicacaoId != null && $this->exceptionEquipamento != null) {
			$this->updateResponseCommunication($output, $code);
		}
	}
	
	private function updateResponseCommunication($output, $statusCode) {
		 
		$this->Comunicacao = ClassRegistry::init('Comunicacao');
		$this->Equipamento = ClassRegistry::init('Equipamento');
		 
		//inicia transação
		$dataSource = $this->Comunicacao->getDataSource();
		$dataSource->begin();
		 
		/*
		 * salva requisição
		*/
		//dados da comunicação
		$comunicacao = array('Comunicacao' => array());
		$comunicacao['Comunicacao']['id'] 				= $this->exceptionComunicacaoId;
		$comunicacao['Comunicacao']['resposta'] 		= json_encode($output);
		$comunicacao['Comunicacao']['respondido_em'] 	= date('Y-m-d H:i:s');
		$comunicacao['Comunicacao']['status_code'] 		= $statusCode;

		//salva
		$this->Comunicacao->save($comunicacao);
		
		$nsu = CakeSession::read("exception.nsu");
		CakeSession::write("exception.nsu", null);
		 
		//incrementa nsu do equipamento
		$this->exceptionEquipamento['Equipamento']['nsu'] = intval($nsu);
		if($this->Equipamento->save($this->exceptionEquipamento)) {
			 
			//encerra transação
			$dataSource->commit();
		} else {
			$dataSource->rollback();
		}
	}
	
}