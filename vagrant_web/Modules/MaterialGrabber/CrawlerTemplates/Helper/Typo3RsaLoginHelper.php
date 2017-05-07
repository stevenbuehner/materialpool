<?php

namespace Modules\MaterialGrabber\CrawlerTemplates\Helper;

use phpseclib\Crypt\RSA;
use phpseclib\Math\BigInteger;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;

class Typo3RsaLoginHelper extends DefaultLoginHelper {

	protected function modifyFormValues(Crawler $crawler, Form $form, array &$formData, $password, $passwordInputName, Crawler $formCrawler) {
		parent::modifyFormValues($crawler, $form, $formData, $password, $passwordInputName,
								 $formCrawler);

		$n = $formData['n'];
		$e = $formData['e'];

		$public_key = [
			'n' => new BigInteger($n, 16),
			'e' => new BigInteger($e, 16),
		];

		$rsa = new RSA();
		$rsa->setEncryptionMode(RSA::ENCRYPTION_PKCS1);
		$rsa->loadKey($public_key);

		$formData['n']                = '';
		$formData['e']                = '';
		$formData[$passwordInputName] = 'rsa:' . base64_encode($rsa->encrypt($password));
	}


}