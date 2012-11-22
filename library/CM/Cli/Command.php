<?php

class CM_Cli_Command {

	/** @var ReflectionClass */
	private $_class;

	/** @var ReflectionMethod */
	private $_method;

	/**
	 * @param ReflectionMethod $method
	 */
	public function __construct(ReflectionMethod $method) {
		$this->_method = $method;
		$this->_class = $method->getDeclaringClass();
	}

	/**
	 * @param CM_Cli_Arguments $arguments
	 * @throws CM_Cli_Exception_InvalidArguments
	 * @return string
	 */
	public function run(CM_Cli_Arguments $arguments) {
		$parameters = array();
		foreach ($this->_method->getParameters() as $param) {
			$parameters[] = $this->_getParamValue($param, $arguments);
		}
		if ($arguments->getNumeric()->getAll()) {
			throw new CM_Cli_Exception_InvalidArguments('Too many arguments provided');
		}
		if ($named = $arguments->getNamed()->getAll()) {
			throw new CM_Cli_Exception_InvalidArguments('Illegal option used: `--' . key($named) . '`');
		}
		return call_user_func_array(array($this->_class->newInstance(), $this->_method->getName()), $parameters);
	}

	/**
	 * @return string
	 */
	public function getHelp() {
		$helpText = $this->_getPackageName() . ' ' . $this->_method->getName();
		foreach ($this->_getRequiredParameters() as $paramName) {
			$helpText .= ' <' . $paramName . '>';
		}
		$helpText .=  PHP_EOL;
		$optionalParameters = $this->_getOptionalParameters();
		if ($optionalParameters) {
			$longestParam = max(array_map('strlen', array_keys($optionalParameters)));
			foreach ($optionalParameters as $paramName => $defaultValue) {
				$helpText .= '   --' . str_pad($paramName, max($longestParam + 5, 20), ' ') . $this->_getParamDoc($paramName) . PHP_EOL;
			}
		}
		return $helpText;
	}

	/**
	 * @return string
	 */
	public function getHelpExtended() {
		return 'Usage: ' . $this->getHelp();
	}

	/**
	 * @param string $packageName
	 * @param string $methodName
	 * @return bool
	 */
	public function match($packageName, $methodName) {
		$methodMatched = ($methodName === $this->_method->getName());
		$packageMatched = ($packageName === $this->_getPackageName());
		return ($packageMatched && $methodMatched);
	}

	/**
	 * @return string[]
	 */
	protected function _getRequiredParameters() {
		$params = array();
		foreach ($this->_method->getParameters() as $param) {
			if (!$param->isOptional()) {
				$params[] = $param->getName();
			}
		}
		return $params;
	}

	/**
	 * @return array
	 */
	protected function _getOptionalParameters() {
		$params = array();
		foreach ($this->_method->getParameters() as $param) {
			if ($param->isOptional()) {
				$params[$param->getName()] = $param->getDefaultValue();
			}
		}
		return $params;
	}

	/**
	 * @param string $paramName
	 * @return string
	 */
	private function _getParamDoc($paramName) {
		$methodDocComment = $this->_method->getDocComment();
		if (!preg_match('/\*\s+@param\s+[^\$]*\s*\$' . preg_quote($paramName) . '\s*([^@\*]*)/', $methodDocComment, $matches)) {
			return null;
		}
		list($docBlock, $description) = $matches;
		return trim($description);
	}

	/**
	 * @param ReflectionParameter   $param
	 * @param CM_Cli_Arguments      $arguments
	 * @throws CM_Cli_Exception_InvalidArguments
	 * @return mixed
	 */
	private function _getParamValue(ReflectionParameter $param, CM_Cli_Arguments $arguments) {
		if (!$param->isOptional()) {
			$argumentsNumeric = $arguments->getNumeric();
			if (!$argumentsNumeric->getAll()) {
				throw new CM_Cli_Exception_InvalidArguments('Missing argument `' . $param->getName() . '`');
			}
			$value = $argumentsNumeric->shift();
		} else {
			$argumentsNamed = $arguments->getNamed();
			if (!$argumentsNamed->has($param->getName())) {
				return $param->getDefaultValue();
			}
			$value = $argumentsNamed->get($param->getName());
			$argumentsNamed->remove($param->getName());
		}
		return $this->_forceType($value, $param);
	}

	/**
	 * @param mixed               $value
	 * @param ReflectionParameter $param
	 * @throws CM_Cli_Exception_InvalidArguments
	 * @return array|mixed
	 */
	private function _forceType($value, ReflectionParameter $param) {
		if ($param->isArray()) {
			return explode(',', $value);
		}
		if (!$param->getClass()) {
			return $value;
		}
		try {
			return $param->getClass()->newInstance($value);
		} catch (Exception $e) {
			throw new CM_Cli_Exception_InvalidArguments('Invalid value for parameter `' . $param->getName() . '`. ' . $e->getMessage());
		}
	}

	/**
	 * @return string
	 */
	private function _getPackageName() {
		return $this->_class->getMethod('getPackageName')->invoke(null);
	}

}