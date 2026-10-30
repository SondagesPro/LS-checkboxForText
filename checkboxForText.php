<?php

/**
 * Description
 *
 * @author Denis Chenu <denis@sondages.pro>
 * @copyright 2016-2024 Denis Chenu <http://www.sondages.pro>
 * @copyright 2016-2024 Fino research and customer relationship <http://www.finoclient.com>
 * @license GPL v3
 * @version 3.0.0
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */
class checkboxForText extends PluginBase
{
	protected $storage = 'DbStorage';
	static protected $name = 'checkboxForText';
	static protected $description = 'Allow to add some checkbox after text question type';

	private $aExistingCheckbox = [
		['type' => "Know", 'value' => 'DNK'],
		['type' => "Want", 'value' => 'NA']
	];

	/**
	 * The settings for this plugin
	 * @Todo Move this to the constructor
	 */
	protected $settings = [
		"notKnowCheckboxTitle" => [
			'type' => 'info',
			'content' => "<div class='h4'>Not know checkbox settings</div>",
		],
		"notKnowCheckboxActive" => [
			"type" => 'select',
			'label' => 'Activate by default',
			'default' => 'M',
			'options' => [
				'Y' => 'Yes',
				'M' => 'On mandatory question',
				'N' => 'No',
			],
		],
		"notKnowCheckbox" => [
			"type" => 'string',
			'label' => 'Value to set for 1st checkbox',
			'default' => 'DNK',
		],
		"notKnowCheckboxNumeric" => [
			"type" => 'float',
			'label' => 'Value to set for 1st checkbox for numeric question type',
			'default' => '999',
		],
		"notKnowCheckboxDate" => [
			"type" => 'string',
			'label' => 'Value to set for 1st checkbox for date/time question type',
			'default' => '1970-01-01 00:00:00',
		],
		"notKnowCheckboxLabel" => [
			"type" => 'string',
			'label' => 'Default label for 1st checkbox',
			'help' => "Do not know for example",
		],
		"notWantCheckboxTitle" => [
			'type' => 'info',
			'content' => "<div class='h4'>Not want checkbox settings</div>",
		],
		"notWantCheckboxActive" => [
			"type" => 'select',
			'label' => 'Activate by default',
			'default' => 'N',
			'options' => [
				'Y' => 'Yes',
				'M' => 'On mandatory question',
				'N' => 'No',
			],
		],
		"notWantCheckbox" => [
			"type" => 'string',
			'label' => 'Value to set for 2nd checkbox',
			'default' => 'NA',
		],
		"notWantCheckboxNumeric" => [
			"type" => 'float',
			'label' => 'Value to set for 2nd checkbox for numeric question type',
			'default' => '998',
		],
		"notWantCheckboxDate" => [
			"type" => 'string',
			'label' => 'Value to set for 2nd checkbox for date/time question type',
			'default' => '1971-02-02 01:01:01',
		],
		"notWantCheckboxLabel" => [
			"type" => 'string',
			'label' => 'Default label for 2nd checkbox',
			'help' => "Not applicable for example",
		],
	];

	/**
	 * Initialize the plugin by subscribing to relevant events.
	 */
	public function init()
	{
		$this->subscribe('beforeActivate');
		$this->subscribe('beforeQuestionRender', 'addNoCheckbox');
		$this->subscribe('beforeSurveySettings');
		$this->subscribe('newSurveySettings');
		$this->subscribe('newQuestionAttributes', 'noCheckboxAttribute');
	}

	/**
	 * Show an alert if toolsSmartDomDocument is not present or inactive.
	 */
	public function beforeActivate()
	{
		$oToolsSmartDomDocument = Plugin::model()->find("name=:name", [":name" => 'toolsDomDocument']);

		// ToolsDomDocument plugin must be installed.
		if (!$oToolsSmartDomDocument) {
			$this->getEvent()->set('message', gT("You must download toolsSmartDomDocument plugin"));
			$this->getEvent()->set('success', false);
			return; // Early exit to avoid further processing
		}

		// ToolsDomDocument plugin must be activated.
		if (!$oToolsSmartDomDocument->active) {
			$this->getEvent()->set('message', gT("You must activate toolsSmartDomDocument plugin"));
			$this->getEvent()->set('success', false);
		}
	}

	/**
	 * Applies new survey settings by setting each provided setting value.
	 */
	public function newSurveySettings()
	{
		$event = $this->event;

		if (empty($event)) {
			throw new CHttpException(403);
		}

		foreach ($event->get('settings') as $name => $value) {
			/* Use survey-specific setting, fallback to global, then default */
			$this->set($name, $value, 'Survey', $event->get('survey'));
		}
	}

	/**
	 * Prepares new survey settings related to custom checkboxes before they are saved.
	 */
	public function beforeSurveySettings()
	{
		$oEvent = $this->event;

		if (empty($oEvent)) {
			throw new CHttpException(403);
		}

		$newSettings = [];
		$oSurvey = Survey::model()->findByPk($oEvent->get('survey'));
		$aLang = $oSurvey->getAllLanguages();

		// Handle settings for 'notKnowCheckbox'
		$this->prepareSurveySetting(
			'notKnowCheckbox',
			$aLang,
			$newSettings,
			$oEvent
		);

		// Handle settings for 'notWantCheckbox' with a default current value of 'M'
		$this->prepareSurveySetting(
			'notWantCheckbox',
			$aLang,
			$newSettings,
			$oEvent,
			'M' // Default current value
		);

		// If any new settings are defined, append them to the survey settings event
		if (!empty($newSettings)) {
			$oEvent->set("surveysettings.{$this->id}", [
				'name' => get_class($this),
				'settings' => $newSettings
			]);
		}
	}

	/**
	 * Adds constructed question attribute settings for custom checkboxes.
	 */
	public function noCheckboxAttribute()
	{
		$event = $this->getEvent();

		if (empty($event)) {
			throw new CHttpException(403);
		}

		$questionAttributes = array();

		// Handle notKnowCheckbox settings
		$notKnowCheckboxValue = $this->get('notKnowCheckbox', null, null,
			$this->settings['notKnowCheckbox']['default']);
		if (!empty($notKnowCheckboxValue)) {
			$questionAttributes['notKnowCheckbox'] = $this->createQuestionAttibute($notKnowCheckboxValue, 1);
			$questionAttributes['notKnowCheckboxLabel'] = $this->createQuestionAttributeLabel(2);
		}

		// Handle notWantCheckbox settings
		$notWantCheckboxValue = $this->get('notWantCheckbox', null, null,
			$this->settings['notWantCheckbox']['default']);
		if (!empty($notWantCheckboxValue)) {
			$questionAttributes['notWantCheckbox'] = $this->createQuestionAttibute($notWantCheckboxValue, 3);
			$questionAttributes['notWantCheckboxLabel'] = $this->createQuestionAttributeLabel(4);
		}

		$event->append('questionAttributes', $questionAttributes);
	}

	private function createQuestionAttibute(string $checkboxValue, int $sortOrder, $types = null)
	{

		// Default to all supported types if none are provided
		if(is_null($types)){
			$types = implode('', $this->getSupportedQuestionTypes(false));
		}

		return [
			"types" => $types,
			'category' => $this->gT('Checkbox'),
			'sortorder' => $sortOrder,
			'inputtype' => 'singleselect',
			'options' => array(
				'D' => $this->gT('Default from survey'),
				'N' => gT('No'),
				'Y' => gT('Yes'),
			),
			'default' => 'D',
			"help" => sprintf($this->gT("Show the %s checkbox (with default label set)."), $checkboxValue),
			"caption" => sprintf($this->gT('Show the %s checkbox'), $checkboxValue)
		];
	}

	private function createQuestionAttributeLabel(int $sortOrder, $types = null)
	{
		// Default to all supported types if none are provided
		if(is_null($types)){
			$types = implode('', $this->getSupportedQuestionTypes(false));
		}

		return [
			"types" => $types,
			'category' => $this->gT('Checkbox'),
			'sortorder' => $sortOrder,
			'inputtype' => 'text',
			'default' => '',
			'i18n' => true,
			"help" => $this->gT('String for this language (by default from survey settings)'),
			"caption" => $this->gT('Text for label')
		];
	}

	/**
	 * Prepares survey settings for a specific checkbox.
	 *
	 * @param  string  $checkboxKey  The key for the checkbox ('notKnowCheckbox' or 'notWantCheckbox').
	 * @param  array  $aLang  Array of supported languages.
	 * @param  array  &$newSettings  Reference to the new settings array to modify.
	 * @param  object  $oEvent  The current event object.
	 * @param  string  $defaultCurrent  The default current value (optional).
	 */
	private function prepareSurveySetting($checkboxKey, $aLang, &$newSettings, $oEvent, $defaultCurrent = '')
	{
		$checkboxValue = $this->get($checkboxKey, null, null, $this->settings[$checkboxKey]['default']);

		// Exit early if the checkbox value is not set
		if (!$checkboxValue) {
			return;
		}

		$activeSettingKey = $checkboxKey.'Active';
		$newSettings[$activeSettingKey] = [
			'type' => 'select',
			'label' => sprintf(
				$this->gT('Activate %s checkbox (by default)'),
				$checkboxValue
			),
			'options' => [
				'Y' => gT('Yes'),
				'M' => $this->gT('On mandatory question'),
				'N' => gT('No'),
			],
			'htmlOptions' => [
				'empty' => sprintf(
					$this->gT('Leave default (%s)'),
					$this->get($activeSettingKey, null, null, $this->settings[$activeSettingKey]['default'])
				),
			],
			'current' => $this->get(
				$activeSettingKey,
				'Survey',
				$oEvent->get('survey'),
				$defaultCurrent
			),
		];

		// Define label settings for each supported language
		foreach ($aLang as $sLang) {
			$labelKey = $checkboxKey.'Label_'.$sLang;
			$sCurrent = $this->get($labelKey, 'Survey', $oEvent->get('survey'), "");
			$newSettings[$labelKey] = [
				'type' => 'string',
				'label' => sprintf(
					$this->gT('%s label for %s checkbox'),
					$sLang,
					$checkboxValue
				),
				'htmlOptions' => [
					'class' => 'form-control'
				],
				'current' => $sCurrent,
			];
		}
	}

	/**
	 * Adds custom checkboxes to survey questions based on defined attributes.
	 */
	public function addNoCheckbox()
	{
		$oEvent = $this->getEvent();
		$supportedTypes = $this->getSupportedQuestionTypes(false);

		// Exit early if the question type is not supported
		if (!in_array($oEvent->get('type'), $supportedTypes)) {
			return;
		}

		$aAttributes = QuestionAttribute::model()->getQuestionAttributes($oEvent->get('qid'));

		// V5.0.0 : language is in l10ns table,
		$oQuestion = Question::model()->find("qid=:qid", [
			":qid" => $oEvent->get('qid'),
		]);

		// Determine if the question is mandatory
		$bIsMandatory = ($oQuestion && $oQuestion->mandatory == "Y");

		foreach ($this->aExistingCheckbox as $aCheckbox) {
			$checkboxType = $aCheckbox['type'];
			$surveySettingKey = 'not'.$checkboxType.'CheckboxActive';
			$sSurveySetting = $this->_getSurveySetting($oEvent->get('surveyId'), $surveySettingKey);
			$checkboxKey = 'not'.$checkboxType.'Checkbox';

			$checkboxActive = $aAttributes[$checkboxKey] ?? null;

			// Check if the checkbox should be active based on attributes and survey settings
			if ($this->shouldActivateCheckbox($checkboxActive, $sSurveySetting, $bIsMandatory)) {
				// Append a CSS class to the question for styling
				$oEvent->set('class', $oEvent->get('class')." text-checkboxfortext");

				// Update the answer based on question type and application version
				$this->updateAnswerBasedOnType($oEvent, $aCheckbox);

				// Register necessary CSS and JavaScript for the custom checkbox
				$this->registerStyleScript();
			}
		}
	}

	protected function getSupportedQuestionTypes(bool $separateByAnswersType = true): array
	{
		if($separateByAnswersType) {
			return [
				'single' => ["S", "T", "U", "N", "D"],
				'multiple' => ["Q", "K"],
			];
		}

		return [
			"S", "T", "U", "N", "D", "!", "Q", "K"
		];
	}

	/**
	 * Determines whether a checkbox should be activated based on its active status, survey settings, and question mandatory status.
	 *
	 * @param  string  $checkboxActive  The active status of the checkbox from attributes.
	 * @param  string  $sSurveySetting  The active status from survey settings.
	 * @param  bool  $bIsMandatory  Whether the question is mandatory.
	 * @return bool                  True if the checkbox should be activated, false otherwise.
	 */
	private
	function shouldActivateCheckbox($checkboxActive, $sSurveySetting, $bIsMandatory)
	{
		return !empty($checkboxActive) && (
				$checkboxActive === "Y" ||
				($checkboxActive === "D" && $sSurveySetting === "Y") ||
				($checkboxActive === "D" && $sSurveySetting === "M" && $bIsMandatory)
			);
	}

	/**
	 * Updates the answer based on the question type and application version.
	 *
	 * @param  object  $oEvent  The current event object.
	 * @param  array  $aCheckbox  The checkbox configuration to apply.
	 */
	private function updateAnswerBasedOnType($oEvent, $aCheckbox)
	{
		$questionType = $oEvent->get('type');

		// Handle single question type
		if (in_array($questionType, $this->getSupportedQuestionTypes()['single'])) {
			$this->updateSingleAnswer($aCheckbox, $oEvent);
		} elseif (in_array($questionType, $this->getSupportedQuestionTypes()['multiple'])) {
			$this->updateMultipleAnswer($aCheckbox, $oEvent);
		}
	}

	/**
	 * Updates single answer type questions with a custom checkbox.
	 *
	 * @param  array  $aCheckbox  Configuration for the checkbox to be added.
	 */
	private function updateSingleAnswer($aCheckbox, $oEvent)
	{
		$sName = "{$oEvent->get('surveyId')}X{$oEvent->get('gid')}X{$oEvent->get('qid')}";

		if ($oEvent->get('type') == "N") {
			$sValue = floatval($this->get('not'.$aCheckbox['type'].'CheckboxNumeric', null, null,
				$this->settings['not'.$aCheckbox['type'].'CheckboxNumeric']['default']));
		}

		// Handle Datepicker
		elseif($oEvent->get('type')=="D") {
			$aAttributes=QuestionAttribute::model()->getQuestionAttributes($oEvent->get('qid'));
			$aDateFormatData=getDateFormatDataForQID($aAttributes,$oEvent->get('surveyId'));
			$oDate=DateTime::createFromFormat("Y-m-d h:i:s", $this->get('not'.$aCheckbox['type'].'CheckboxDate',null,null,$this->settings['not'.$aCheckbox['type'].'CheckboxDate']['default']));
			$sValue=$oDate->format($aDateFormatData['phpdate']);
		}
		else {
			$sValue = $this->get('not'.$aCheckbox['type'].'Checkbox', null, null,
				$this->settings['not'.$aCheckbox['type'].'Checkbox']['default']);
		}

		$sHtmlAnswers = $oEvent->get('answers');
		/* Get the actual value */
		$sActualValue = $_SESSION["survey_{$oEvent->get('surveyId')}"][$sName];

		if ($sActualValue == $sValue) {
			$sHtmlAnswers = str_replace('value="'.$sValue.'"', 'value=""', $sHtmlAnswers);
			$sHtmlAnswers = str_replace($sValue.'</textarea>', '</textarea>', $sHtmlAnswers);
		}
		/* Get the label */
		$aAttributes = QuestionAttribute::model()->getQuestionAttributes($oEvent->get('qid'));
		$sLabel = $aAttributes['not'.$aCheckbox['type'].'CheckboxLabel'][App()->language];
		if (!$sLabel) {
			$sLabel = $this->getDefaultLabel($aCheckbox['type']);
		}

		$sHtmlAdd = CHtml::checkBox($sName, $sActualValue == $sValue, array(
			"value" => $sValue,
			'id' => "answer{$sName}_{$aCheckbox['value']}",
			"data-checkboxFor" => "answer{$sName}",
			"data-updatevalue" => $sValue,
		));
		$sHtmlAdd .= CHtml::label($sLabel, "answer{$sName}_{$aCheckbox['value']}",
			array('class' => "checkbox-label control-label", "style" => 'margin-left:10px;'));
		$sHtmlAdd = CHtml::tag("div", array("class" => "addedcheckbox-item addcheckbox-plugin"), $sHtmlAdd);

		$oEvent->set('answers', $sHtmlAnswers.$sHtmlAdd);

	}

	/**
	 * Placeholder for updating multiple answer type questions with a custom checkbox.
	 *
	 * @param  array  $aCheckbox  Configuration for the checkbox to be added.
	 */
	private function updateMultipleAnswer($aCheckbox, $oEvent, $sLabel = "")
	{
		$sBaseName = "{$oEvent->get('surveyId')}X{$oEvent->get('gid')}X{$oEvent->get('qid')}";

		if ($oEvent->get('type') == "K") {
			$sValue = floatval($this->get('not'.$aCheckbox['type'].'CheckboxNumeric', null, null,
				$this->settings['not'.$aCheckbox['type'].'CheckboxNumeric']['default']));
		} else {
			$sValue = $this->get('not'.$aCheckbox['type'].'Checkbox', null, null,
				$this->settings['not'.$aCheckbox['type'].'Checkbox']['default']);
		}

		/* Get the label */
		$aAttributes = QuestionAttribute::model()->getQuestionAttributes($oEvent->get('qid'));
		$sLabel = $aAttributes['not'.$aCheckbox['type'].'CheckboxLabel'][App()->language];
		if (!$sLabel) {
			$sLabel = $this->getDefaultLabel($aCheckbox['type']);
		}

		$domAnswers = new \toolsDomDocument\SmartDOMDocument();

		$domAnswers->loadPartialHTML($oEvent->get('answers'));

		$oSubQuestions = Question::model()->findAll("parent_qid = :qid", array(":qid" => $oEvent->get('qid')));

		foreach ($oSubQuestions as $oSubQuestion) {

			$sName = $sBaseName.$oSubQuestion->title;
			$input = $domAnswers->getElementById("answer{$sName}");

			if ($input) {
				$sActualValue = $_SESSION["survey_{$oEvent->get('surveyId')}"][$sName];
				if ($sActualValue == $sValue) {
					$isChecked = true;
					if ($input->tagName == "input") {
						$input->setAttribute("value", '');
					} elseif ($input->tagName == "textarea") {
						//~ @todo
					}
				} else {
					$isChecked = false;
				}
				$newWrapper = $domAnswers->createElement("div");
				$newWrapper->setAttribute("class", 'addedcheckbox-item addcheckbox-plugin');
				$newInput = $domAnswers->createElement("input");
				$newInput->setAttribute("type", 'checkbox');
				$newInput->setAttribute("name", $sName);
				$newInput->setAttribute("data-checkboxfor", "answer{$sName}");
				$newInput->setAttribute("id", "answer{$sName}_{$sValue}");
				$newInput->setAttribute("value", "{$sValue}");
				if ($isChecked) {
					$newInput->setAttribute("checked", "checked");
				}
				$newWrapper->appendChild($newInput);
				$newLabel = $domAnswers->createElement("label", $sLabel);
				$newLabel->setAttribute("for", "answer{$sName}_{$sValue}");
				$newLabel->setAttribute("class", "checkbox-label control-label");
				$newWrapper->appendChild($newLabel);
				$newLabel->setAttribute("style", "margin-left:10px;");
				$input->parentNode->appendChild($newWrapper);
			}
		}
		$oEvent->set('answers', $domAnswers->saveHTMLExact());
	}


	/**
	 * Registers necessary CSS and JavaScript files for the custom checkbox functionality.
	 */
	private function registerStyleScript()
	{
		$assetUrl = Yii::app()->assetManager->publish(dirname(__FILE__).'/assets/');
		App()->clientScript->registerCssFile($assetUrl.'/checkboxForText.css');
		App()->clientScript->registerScriptFile($assetUrl.'/checkboxForText.js');
	}

	/**
	 * Retrieves the default label for a given checkbox type.
	 *
	 * @param  string  $type  The type of checkbox.
	 * @return string The default label.
	 */
	private function getDefaultLabel($type)
	{
		$sLabel = $this->get('not'.$type.'CheckboxLabel_'.App()->getLanguage(), 'Survey',
			$this->getEvent()->get('surveyId'));
		if (!$sLabel) {
			$sLabel = $this->get('not'.$type.'CheckboxLabel', null, null, "");
		}
		if (!$sLabel) {
			$sLabel = $this->get('not'.$type.'Checkbox', null, null,
				$this->settings['not'.$type.'Checkbox']['default']);
		}
		return $sLabel;
	}

	/**
	 * Get the current setting for a survey
	 *
	 * @param  int  $surveyId  The ID of the survey.
	 * @param  string  $setting  The name of the setting to retrieve.
	 * @return mixed           The value of the setting.
	 */
	private function _getSurveySetting($surveyId, $setting)
	{
		$value = $this->get($setting, 'Survey', $surveyId);
		if (empty($value)) {
			return $this->get($setting, null, null, $this->settings[$setting]['default'] ?? null);
		}
		return $value;
	}

}
