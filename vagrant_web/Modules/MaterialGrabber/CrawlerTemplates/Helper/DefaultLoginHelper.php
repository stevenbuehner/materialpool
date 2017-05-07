<?php

namespace Modules\MaterialGrabber\CrawlerTemplates\Helper;

use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;

class DefaultLoginHelper {


	/**
	 * @param Client $client
	 * @param        $url
	 * @param        $password
	 * @param array  $overridePostData
	 * @param string $submitFieldName
	 * @return bool LoginSuccess
	 */
	public function login(Client $client, $url, $password, $overridePostData = [], $submitFieldName = 'einloggen') {

		// Overrideable hook
		$firstTimeCrawler = $this->initialRequest($client, $url);

		// Overrideable hook
		$form = $this->getLoginForm($firstTimeCrawler, $submitFieldName);
		if ($form === FALSE) {
			return FALSE;
		}

		$formCrawler       = new Crawler($form->getFormNode());
		$passwordInputName = $formCrawler->filter('input[type=password]')->attr('name');
		$formData          = $form->getPhpValues();


		$formData                     = array_merge($formData, $overridePostData);
		$formData[$passwordInputName] = $password;

		// Hook
		$this->modifyFormValues($firstTimeCrawler, $form, $formData, $password, $passwordInputName, $formCrawler);

		$form->setValues($formData);

		// Hook
		$this->beforeSubmit($client, $form);

		$submitResultCrawler = $client->submit($form);

		// Hook
		return $this->afterSubmit($client, $submitResultCrawler);
	}

	/**
	 * @param Client $client
	 * @return Crawler
	 */
	protected function initialRequest(Client $client, $url) {
		return $client->request(
			'GET',
			$url
		);
	}

	/**
	 * @param Crawler $crawler
	 * @param string  $submitFieldName
	 * @return Form | false
	 */
	protected function getLoginForm(Crawler $crawler, $submitFieldName) {
		$form = FALSE;

		$formCrawler = $crawler->selectButton($submitFieldName);
		if ($formCrawler->count() === 1) {
			$form = $formCrawler->form();
		} else {
			// No form fits the description => try using any form, but only, if there is only ONE form present

			$formCrawler = $crawler->filter('form');
			if ($formCrawler->count() === 1) {
				$form = $formCrawler->form();
			}
		}

		return $form;
	}

	/**
	 * @param Crawler $crawler
	 * @param Form    $form
	 * @param array   $formData
	 * @param string  $password
	 * @param string  $passwordInputName
	 * @param Crawler $formCrawler
	 */
	protected function modifyFormValues(Crawler $crawler, Form $form, array &$formData, $password, $passwordInputName, Crawler $formCrawler) {

	}

	/**
	 * @param Client $client
	 * @param Form   $form
	 */
	protected function beforeSubmit(Client $client, Form $form) {

	}

	/**
	 * @param Client  $client
	 * @param Crawler $submitResultCrawler
	 * @return bool LoginSuccess
	 */
	protected function afterSubmit(Client $client, Crawler $submitResultCrawler) {
		return $client->getResponse()->getStatus() == 200;
	}
}