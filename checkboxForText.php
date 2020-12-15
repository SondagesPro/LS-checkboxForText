<?php
/**
 * Description
 *
 * @author Denis Chenu <denis@sondages.pro>
 * @copyright 2016-2020 Denis Chenu <http://www.sondages.pro>
 * @copyright 2016-2017 Extract recherche marketing <http://www.extractmarketing.com>
 * @license GPL v3
 * @version 2.1.2
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

  private $aExistingCheckbox=array(
    array('type'=>"Know",'value'=>'DNK'),
    array('type'=>"Want",'value'=>'NA')
  );

  private $translation=array(
    'Checkbox'=>array('fr'=>"Case à cocher"),
    'Default from survey'=>array('fr'=>"Par défaut selon questionnaire"),
    'Show the %s checkbox (with default label set).'=>array('fr'=>"Montre la case %s (avec le label par défaut)"),
    'Show the %s checkbox'=>array('fr'=>"Montre la case à cocher %s"),
    'String for this language (by default from survey settings)'=>array('fr'=>"Texte pour cette langue (si vide celle des paramètres du questionnaire)"),
    'Text for label'=>array('fr'=>"Texte pour le label"),
    'On mandatory question'=>array('fr'=>"Sur les questions obligatoires"),
    'Activate %s checkbox (by default)'=>array('fr'=>"Activer la case %s (par défaut)"),
    '%s label for %s checkbox'=>array('fr'=>"Label %s pour la case %s")
  );
  /**
   * The settings for this plugin
   * @Todo Move this to the constructor
   */
  protected $settings=array(
      "notKnowCheckboxTitle"=>array(
        'type' => 'info',
        'content' => "<div class='h4'>Not know checkbox settings</div>",
      ),
      "notKnowCheckboxActive"=>array(
        "type"=>'select',
        'label'=>'Activate by default',
        'default' => 'M',
        'options'=>array(
          'Y'=>'Yes',
          'M'=>'On mandatory question',
          'N'=>'No',
        ),
      ),
      "notKnowCheckbox"=>array(
        "type"=>'string',
        'label'=>'Value to set for 1st checkbox',
        'default'=>'DNK',
      ),
      "notKnowCheckboxNumeric"=>array(
        "type"=>'float',
        'label'=>'Value to set for 1st checkbox for numeric question type',
        'default'=>'999',
      ),
      "notKnowCheckboxDate"=>array(
        "type"=>'string',
        'label'=>'Value to set for 1st checkbox for date/time question type',
        'default'=>'1970-01-01 00:00:00',
      ),
      "notKnowCheckboxLabel"=>array(
        "type"=>'string',
        'label'=>'Default label for 1st checkbox',
        'help'=>"Do not know for example",
      ),

      "notWantCheckboxTitle"=>array(
        'type' => 'info',
        'content' => "<div class='h4'>Not want checkbox settings</div>",
      ),
      "notWantCheckboxActive"=>array(
        "type"=>'select',
        'label'=>'Activate by default',
        'default' => 'N',
        'options'=>array(
          'Y'=>'Yes',
          'M'=>'On mandatory question',
          'N'=>'No',
        ),
      ),
      "notWantCheckbox"=>array(
        "type"=>'string',
        'label'=>'Value to set for 2nd checkbox',
        'default'=>'NA',
      ),
      "notWantCheckboxNumeric"=>array(
        "type"=>'float',
        'label'=>'Value to set for 2nd checkbox for numeric question type',
        'default'=>'998',
      ),
      "notWantCheckboxDate"=>array(
        "type"=>'string',
        'label'=>'Value to set for 2nd checkbox for date/time question type',
        'default'=>'1971-02-02 01:01:01',
      ),
      "notWantCheckboxLabel"=>array(
        "type"=>'string',
        'label'=>'Default label for 2nd checkbox',
        'help'=>"Not applicable for example",
      ),
    );
    /**
    * Add function to be used in beforeQuestionRender event
    */
    public function init()
    {
      $this->subscribe('beforeActivate');

      $this->subscribe('beforeQuestionRender','addNoCheckbox');
      $this->subscribe('beforeSurveySettings');
      $this->subscribe('newSurveySettings');
      $this->subscribe('newQuestionAttributes','noCheckboxAttribute');
    }

    /**
     * Show an alert if toolsSmartDomDocument is not here
     */
    public function beforeActivate()
    {
        if (!$this->getEvent()) {
            throw new CHttpException(403);
        }
        $oToolsSmartDomDocument = Plugin::model()->find("name=:name",array(":name"=>'toolsDomDocument'));
        if(!$oToolsSmartDomDocument)
        {
            $this->getEvent()->set('message', gT("You must download toolsSmartDomDocument plugin"));
            $this->getEvent()->set('success', false);
        }
        elseif(!$oToolsSmartDomDocument->active)
        {
            $this->getEvent()->set('message', gT("You must activate toolsSmartDomDocument plugin"));
            $this->getEvent()->set('success', false);
        }
    }
    /**
     * Adding the constructed Question attribute settings
     */
    public function noCheckboxAttribute()
    {
      if (!$this->getEvent()) {
          throw new CHttpException(403);
      }

      $event = $this->getEvent();
      if(intval(App()->getConfig('versionnumber'))< 3) {
        $questionType = "STUNDQK";
      } else {
        $questionType = "STUND";
      }
      $questionAttributes = array();
      if($notKnowCheckboxValue=$this->get('notKnowCheckbox',null,null,$this->settings['notKnowCheckbox']['default']))
      {
        $questionAttributes['notKnowCheckbox']=array(
          "types"=>$questionType,
          'category'=>$this->_translate('Checkbox'),
          'sortorder'=>1,
          'inputtype'=>'singleselect',
          'options'=>array(
              'D'=>$this->_translate('Default from survey'),
              'N'=>gT('No'),
              'Y'=>gT('Yes'),
          ),
          'default'=>'D',
          "help"=>sprintf($this->_translate("Show the %s checkbox (with default label set)."),$notKnowCheckboxValue),
          "caption"=>sprintf($this->_translate('Show the %s checkbox'),$notKnowCheckboxValue)
        );
        $questionAttributes['notKnowCheckboxLabel']=array(
          "types"=>$questionType,
          'category'=>$this->_translate('Checkbox'),
          'sortorder'=>2,
          'inputtype'=>'text',
          'default'=>'',
          'i18n'=>true,
          "help"=>$this->_translate('String for this language (by default from survey settings)'),
          "caption"=>$this->_translate('Text for label')
        );
      }
      if($notWantCheckboxValue=$this->get('notWantCheckbox',null,null,$this->settings['notWantCheckbox']['default']))
      {
        $questionAttributes['notWantCheckbox']=array(
          "types"=>"STUND",//"QK",
          'category'=>$this->_translate('Checkbox'),
          'sortorder'=>3,
          'inputtype'=>'singleselect',
          'options'=>array(
              'D'=>$this->_translate('Default from survey'),
              'N'=>gT('No'),
              'Y'=>gT('Yes'),
          ),
          'default'=>'D',
          "help"=>sprintf($this->_translate("Show the %s checkbox (with default label set)."),$notWantCheckboxValue),
          "caption"=>sprintf($this->_translate('Show the %s checkbox'),$notWantCheckboxValue)
        );

        $questionAttributes['notWantCheckboxLabel']=array(
          "types"=>"STUND",//"QK",
          'category'=>$this->_translate('Checkbox'),
          'sortorder'=>4,
          'inputtype'=>'text',
          'default'=>'',
          'i18n'=>true,
          "help"=>$this->_translate('String for this language (by default from survey settings)'),
          "caption"=>$this->_translate('Text for label')
        );
      }
      if(!empty($questionAttributes)) {
        $questionAttributes['needEmEvent']=array(
          "types"=>"STUNDQK",
          'category'=>$this->_translate('Checkbox'),
          'sortorder'=>10,
          'inputtype'=>'switch',
          'default'=>0,
          'i18n'=>false,
          "caption"=>$this->_translate('Need to manage expression manager event'),
          "help"=>$this->_translate('If you have condition in same page : you need to chech this.')
        );
      }
      $event->append('questionAttributes', $questionAttributes);

    }

    public function beforeSurveySettings()
    {
      if (!$this->getEvent()) {
          throw new CHttpException(403);
      }

      $oEvent = $this->event;
      $newSettings=array();
      $oSurvey=Survey::model()->findByPk($oEvent->get('survey'));
      $aLang=$oSurvey->getAllLanguages();
      $notKnowCheckboxValue=$this->get('notKnowCheckbox',null,null,$this->settings['notKnowCheckbox']['default']);
      if($notKnowCheckboxValue)
      {
        $newSettings['notKnowCheckboxActive']=array(
          'type'=>'select',
          'label'=>sprintf($this->_translate('Activate %s checkbox (by default)'),$notKnowCheckboxValue),
          'options'=>array(
            'Y'=>gT('Yes'),
            'M'=>$this->_translate('On mandatory question'),
            'N'=>gT('No'),
          ),
          'htmlOptions' => array(
            'empty'=> sprintf($this->_translate('Leave default (%s)'),$this->get('notKnowCheckboxActive',null,null,$this->settings['notKnowCheckboxActive']['default'])),
          ),
          'current'=>$this->get('notKnowCheckboxActive','Survey',$oEvent->get('survey'),''),
        );
        foreach($aLang as $sLang)
        {
          $sCurrent=$this->get('notKnowCheckboxLabel_'.$sLang,'Survey',$oEvent->get('survey'),"");
          $newSettings['notKnowCheckboxLabel_'.$sLang]=array(
            'type'=>'string',
            'label'=>sprintf($this->_translate('%s label for %s checkbox'),$sLang,$notKnowCheckboxValue),
            'htmlOptions'=>array(
              'class'=>'form-control'
            ),
            'current'=>$sCurrent,
          );
        }
      }
      $notWantCheckboxValue=$this->get('notWantCheckbox',null,null,$this->settings['notWantCheckbox']['default']);
      if($notWantCheckboxValue)
      {
        $newSettings['notWantCheckboxActive']=array(
          'type'=>'select',
          'label'=>sprintf($this->_translate('Activate %s checkbox (by default)'),$notWantCheckboxValue),
          'options'=>array(
            'Y'=>gT('Yes'),
            'M'=>$this->_translate('On mandatory question'),
            'N'=>gT('No'),
          ),
          'htmlOptions' => array(
            'empty'=> sprintf($this->_translate('Leave default (%s)'),$this->get('notWantCheckboxActive',null,null,$this->settings['notWantCheckboxActive']['default'])),
          ),
          'current'=>$this->get('notWantCheckboxActive','Survey',$oEvent->get('survey'),'M'),
        );
        $sCurrent=$this->get('notWantCheckboxLabel_'.$sLang,'Survey',$oEvent->get('survey'),"");
        foreach($aLang as $sLang)
        {
          $newSettings['notWantCheckboxLabel_'.$sLang]=array(
            'type'=>'string',
            'label'=>sprintf($this->_translate('%s label for %s checkbox'),$sLang,$notWantCheckboxValue),
            'htmlOptions'=>array(
              'class'=>'form-control'
            ),
            'current'=>$sCurrent,
          );
        }
      }
      if(count($newSettings))
      {
        $oEvent->set("surveysettings.{$this->id}", array(
              'name' => get_class($this),
              'settings' => $newSettings
        ));
      }
    }

    public function newSurveySettings()
    {
        if (!$this->getEvent()) {
            throw new CHttpException(403);
        }

        $event = $this->event;
        foreach ($event->get('settings') as $name => $value)
        {
            /* In order use survey setting, if not set, use global, if not set use default */
            $this->set($name, $value, 'Survey', $event->get('survey'));
        }
    }

    public function addNoCheckbox()
    {
      if (!$this->getEvent()) {
          throw new CHttpException(403);
      }

      $oEvent=$this->getEvent();

      if(in_array($oEvent->get('type'),array("S","T","U","N","D","Q","K")))
      {
        $aAttributes=QuestionAttribute::model()->getQuestionAttributes($oEvent->get('qid'));
        $oQuestion=Question::model()->find("qid=:qid and language=:language", array(":qid"=>$oEvent->get('qid'),":language"=>App()->language));
        $bIsMandatory=$oQuestion->mandatory=="Y";
        foreach($this->aExistingCheckbox as $aCheckbox)
        {
          $sSurveySetting=$this->_getSurveySetting($oEvent->get('surveyId'),'not'.$aCheckbox['type'].'CheckboxActive');
          if( !empty($aAttributes['not'.$aCheckbox['type'].'Checkbox']) && (
            $aAttributes['not'.$aCheckbox['type'].'Checkbox']=="Y"
            || ($aAttributes['not'.$aCheckbox['type'].'Checkbox']=="D" && $sSurveySetting=="Y")
            || ($aAttributes['not'.$aCheckbox['type'].'Checkbox']=="D" && $sSurveySetting=="M" && $bIsMandatory )
            ))
          {
            $this->getEvent()->set('class',$this->getEvent()->get('class')." text-checkboxfortext");
            if(in_array($oEvent->get('type'),array("S","T","U","N","D")))
            {
              if(intval(App()->getConfig('versionnumber'))< 3) {
                $this->_updateSingleAnswer_2($aCheckbox);
              } else {
                $this->_updateSingleAnswer($aCheckbox);
              }
            }
            elseif(in_array($oEvent->get('type'),array("Q","K")))
            {
              if(intval(App()->getConfig('versionnumber'))< 3) {
                $this->_updateMultipleAnswer_2($aCheckbox);
              } else {
                $this->_updateMultipleAnswer($aCheckbox);
              }
            }
            $this->registerStyleScript();
          }
        }

      }
    }

    private function _updateSingleAnswer_2($aCheckbox)
    {
      $oEvent=$this->getEvent();
      $sName="{$oEvent->get('surveyId')}X{$oEvent->get('gid')}X{$oEvent->get('qid')}";
      if($oEvent->get('type')=="N")
      {
        $sValue=floatval($this->get('not'.$aCheckbox['type'].'CheckboxNumeric',null,null,$this->settings['not'.$aCheckbox['type'].'CheckboxNumeric']['default']));
      }
      elseif($oEvent->get('type')=="D")
      {
        $aAttributes=QuestionAttribute::model()->getQuestionAttributes($oEvent->get('qid'));
        $aDateFormatData=getDateFormatDataForQID($aAttributes,$oEvent->get('surveyId'));
        $oDate=DateTime::createFromFormat("Y-m-d h:i:s", $this->get('not'.$aCheckbox['type'].'CheckboxDate',null,null,$this->settings['not'.$aCheckbox['type'].'CheckboxDate']['default']));
        $sValue=$oDate->format($aDateFormatData['phpdate']);
      }
      else
      {
        $sValue=$this->get('not'.$aCheckbox['type'].'Checkbox',null,null,$this->settings['not'.$aCheckbox['type'].'Checkbox']['default']);
      }
      $sHtmlAnswers =$oEvent->get('answers');
      /* Get the actual value */
      $sActualValue=$_SESSION["survey_{$oEvent->get('surveyId')}"][$sName];
      if($sActualValue==$sValue)
      {
        $sHtmlAnswers=str_replace('value="'.$sValue.'"','value=""',$sHtmlAnswers);
        $sHtmlAnswers=str_replace($sValue.'</textarea>','</textarea>',$sHtmlAnswers);
      }
      /* Get the label */
      $aAttributes=QuestionAttribute::model()->getQuestionAttributes($oEvent->get('qid'));
      $sLabel=$aAttributes['not'.$aCheckbox['type'].'CheckboxLabel'][App()->language];
      if(!$sLabel)
      {
        $sLabel=$this->getDefaultLabel($aCheckbox['type']);
      }

      $sHtmlAdd =CHtml::checkBox($sName,$sActualValue==$sValue,array(
        "value"=>$sValue,
        'id'=>"answer{$sName}_{$aCheckbox['value']}",
        "data-checkboxFor"=>"answer{$sName}",
        "data-updatevalue"=>$aAttributes['needEmEvent'] ? $sValue:"",
      ));
      $sHtmlAdd.=CHtml::label($sLabel,"answer{$sName}_{$aCheckbox['value']}",array('class'=>"checkbox-label control-label"));
      $sHtmlAdd=CHtml::tag("div",array("class"=>"addedcheckbox-item addcheckbox-plugin"),$sHtmlAdd);
      $oEvent->set('answers',$sHtmlAnswers.$sHtmlAdd);
    }

    private function _updateMultipleAnswer_2($aCheckbox,$sLabel="")
    {
      $oEvent=$this->getEvent();
      $sBaseName="{$oEvent->get('surveyId')}X{$oEvent->get('gid')}X{$oEvent->get('qid')}";
      if($oEvent->get('type')=="K")
      {
        $sValue=floatval($this->get('not'.$aCheckbox['type'].'CheckboxNumeric',null,null,$this->settings['not'.$aCheckbox['type'].'CheckboxNumeric']['default']));
      }
      else
      {
        $sValue=$this->get('not'.$aCheckbox['type'].'Checkbox',null,null,$this->settings['not'.$aCheckbox['type'].'Checkbox']['default']);
      }
      /* Get the label */
      $aAttributes=QuestionAttribute::model()->getQuestionAttributes($oEvent->get('qid'));
      $sLabel=$aAttributes['not'.$aCheckbox['type'].'CheckboxLabel'][App()->language];
      if(!$sLabel)
      {
        $sLabel=$this->getDefaultLabel($aCheckbox['type']);
      }
      $domAnswers = new \toolsDomDocument\SmartDOMDocument();
      $domAnswers->loadPartialHTML($this->event->get('answers'));
      $oSubQuestions=Question::model()->findAll("parent_qid=:qid and language=:language", array(":qid"=>$oEvent->get('qid'),":language"=>App()->language));

      foreach($oSubQuestions as $oSubQuestion)
      {
        $sName=$sBaseName.$oSubQuestion->title;
        $input=$domAnswers->getElementById("answer{$sName}");
        if($input)
        {
          $sActualValue=$_SESSION["survey_{$oEvent->get('surveyId')}"][$sName];
          if($sActualValue==$sValue)
          {
            $isChecked=true;
            if($input->tagName=="input"){
              $input->setAttribute("value",'');
            }elseif($input->tagName=="textarea"){
              //~ @todo
            }
          }
          else
          {
            $isChecked=false;
          }
          $newWrapper=$domAnswers->createElement("div");
          $newWrapper->setAttribute("class",'addedcheckbox-item addcheckbox-plugin');
          $newInput=$domAnswers->createElement("input");
          $newInput->setAttribute("type",'checkbox');
          $newInput->setAttribute("name",$sName);
          $newInput->setAttribute("data-checkboxfor","answer{$sName}");
          $newInput->setAttribute("id","answer{$sName}_{$sValue}");
          $newInput->setAttribute("value","{$sValue}");
          if($isChecked)
          {
            $newInput->setAttribute("checked","checked");
          }
          $newWrapper->appendChild($newInput);
          $newLabel=$domAnswers->createElement("label",$sLabel);
          $newLabel->setAttribute("for","answer{$sName}_{$sValue}");
          $newLabel->setAttribute("class","checkbox-label control-label");
          $newWrapper->appendChild($newLabel);
          $input->parentNode->appendChild($newWrapper);
        }

      }
      $oEvent->set('answers',$domAnswers->saveHTMLExact());
    }

    private function _updateSingleAnswer($aCheckbox)
    {
        $this->_updateSingleAnswer_2($aCheckbox);
    }

    private function _updateMultipleAnswer($aCheckbox)
    {

    }

    private function getDefaultLabel($type)
    {
      $sLabel=$this->get('not'.$type.'CheckboxLabel_'.App()->getLanguage(),'Survey',$this->getEvent()->get('surveyId'));
      if(!$sLabel)
      {
        $sLabel=$this->get('not'.$type.'CheckboxLabel',null,null,"");
      }
      if(!$sLabel)
      {
        $sLabel=$this->get('not'.$type.'Checkbox',null,null,$this->settings['not'.$type.'Checkbox']['default']);
      }
      return $sLabel;
    }
    private function registerStyleScript()
    {
      $assetUrl = Yii::app()->assetManager->publish(dirname(__FILE__) . '/assets/');
      App()->clientScript->registerCssFile($assetUrl.'/checkboxForText.css');
      App()->clientScript->registerScriptFile($assetUrl.'/checkboxForText.js');
    }

    /**
     * Get the current setting for a survey
     * @param $surveyid
     * @param string $setting name
     * @return mixed
     */
    private function _getSurveySetting($surveyId,$setting)
    {
      $value = $this->get($setting,'Survey',$surveyId);
      if(empty($value)) {
        $default = isset($this->settings[$setting]['default']) ? $this->settings[$setting]['default'] : null;
        return $this->get($setting,null,null,$this->settings[$setting]['default']);
      }
      return $value;
    }
    private function _translate($string)
    {
      if(isset($this->translation[$string][Yii::app()->language]))
      {
        return $this->translation[$string][Yii::app()->language];
      }
      return $string;
    }
}
