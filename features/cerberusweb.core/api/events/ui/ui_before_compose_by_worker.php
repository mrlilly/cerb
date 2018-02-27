<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2018, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.ai/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.ai	    http://webgroup.media
***********************************************************************/

class Event_MailBeforeUiComposeByWorker extends Extension_DevblocksEvent {
	const ID = 'event.mail.compose.pre.ui.worker';
	
	function __construct($manifest) {
		parent::__construct($manifest);
		$this->_event_id = self::ID;
	}
	
	static function trigger($trigger_id, $scope=[], $worker_id, &$actions) {
		$events = DevblocksPlatform::services()->event();
		return $events->trigger(
			new Model_DevblocksEvent(
				self::ID,
				array(
					'_caller_actions' => &$actions,
					'scope' => $scope,
					'worker_id' => $worker_id,
					'_whisper' => array(
						'_trigger_id' => array($trigger_id),
					),
				)
			)
		);
	}
	
	/**
	 *
	 * @param Model_TriggerEvent $trigger
	 * @param integer $context_id
	 * @return Model_DevblocksEvent
	 */
	function generateSampleEventModel(Model_TriggerEvent $trigger, $context_id=null) {
		$event_model = new Model_DevblocksEvent(
			self::ID,
			array(
				'scope' => [
					'form_id' => 'frmComposePeek1234',
					'group_id' => 123,
					'bucket_id' => 123,
				],
			)
		);
		
		if(false != ($active_worker = CerberusApplication::getActiveWorker())) {
			$worker_id = $active_worker->id;
		} else {
			$worker_id = DAO_Worker::random();
		}
		
		$event_model->params['worker_id'] = $worker_id;
		
		return $event_model;
	}
	
	function setEvent(Model_DevblocksEvent $event_model=null, Model_TriggerEvent $trigger=null) {
		$labels = [];
		$values = [];

		$prefix = 'Scope ';
		$labels['scope.form_id'] = $prefix.'form ID';
		$labels['scope.group_id'] = $prefix.'group ID';
		$labels['scope.bucket_id'] = $prefix.'bucket ID';
		
		/**
		 * Behavior
		 */
		
		$merge_labels = array();
		$merge_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_BEHAVIOR, $trigger, $merge_labels, $merge_values, null, true);

			// Merge
			CerberusContexts::merge(
				'behavior_',
				'',
				$merge_labels,
				$merge_values,
				$labels,
				$values
			);
		
		// Add the worker_id
		@$worker_id = $event_model->params['worker_id'];
		
		/**
		 * Current worker
		 */
		$worker_labels = array();
		$worker_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $worker_id, $worker_labels, $worker_values, 'Current Worker:', true);
				
			// Merge
			CerberusContexts::merge(
				'current_worker_',
				'',
				$worker_labels,
				$worker_values,
				$labels,
				$values
			);
		
		/**
		 * Caller actions
		 */
		
		if(isset($event_model->params['_caller_actions'])) {
			$values['_caller_actions'] =& $event_model->params['_caller_actions'];
		}
		
		if(isset($event_model->params['scope'])) {
			$values['scope'] = $event_model->params['scope'];
		}
		
		$this->setLabels($labels);
		$this->setValues($values);
	}
	
	function getValuesContexts($trigger) {
		$vals_to_ctx = parent::getValuesContexts($trigger);
		
		$vals = [
			'behavior_id' => array(
				'label' => 'Behavior',
				'context' => CerberusContexts::CONTEXT_BEHAVIOR,
			),
			'behavior_bot_id' => array(
				'label' => 'Bot',
				'context' => CerberusContexts::CONTEXT_BOT,
			),
			'current_worker_id' => array(
				'label' => 'Current worker',
				'context' => CerberusContexts::CONTEXT_WORKER,
			),
		];
		
		$vals_to_ctx = array_merge($vals_to_ctx, $vals);
		DevblocksPlatform::sortObjects($vals_to_ctx, '[label]');
		
		return $vals_to_ctx;
	}
	
	function getConditionExtensions(Model_TriggerEvent $trigger) {
		$labels = $this->getLabels($trigger);
		$types = $this->getTypes();
		
		$labels['scope.form_id'] = 'Scope form ID';
		$labels['scope.group_id'] = 'Scope group ID';
		$labels['scope.bucket_id'] = 'Scope bucket ID';
		
		$types['scope.form_id'] = Model_CustomField::TYPE_SINGLE_LINE;
		$types['scope.group_id'] = Model_CustomField::TYPE_NUMBER;
		$types['scope.bucket_id'] = Model_CustomField::TYPE_NUMBER;

		$conditions = $this->_importLabelsTypesAsConditions($labels, $types);
		
		return $conditions;
	}
	
	function renderConditionExtension($token, $as_token, $trigger, $params=[], $seq=0) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);
		
		
		$tpl->clearAssign('namePrefix');
		$tpl->clearAssign('params');
	}
	
	function runConditionExtension($token, $as_token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$pass = true;
		return $pass;
	}
	
	function getActionExtensions(Model_TriggerEvent $trigger) {
		$actions =
			array(
				'exec_jquery' => array('label' =>'Execute jQuery script'),
			)
			;
		
		return $actions;
	}
	
	function renderActionExtension($token, $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);

		if(!is_null($seq))
			$tpl->assign('namePrefix','action'.$seq);

		$labels = $this->getLabels($trigger);
		$tpl->assign('token_labels', $labels);
			
		switch($token) {
			case 'exec_jquery':
				$default_jquery =
					"var \$frm = \$('#{{scope.form_id}}');\n".
					"var \$select_group = \$frm.find('select[name=group_id]);\n".
					"\n".
					"// Enter your jQuery script here\n".
					"";
				
				$tpl->assign('default_jquery', $default_jquery);
				
				$tpl->display('devblocks:cerberusweb.core::events/ui/reply/action_exec_jquery.tpl');
				break;
		}
		
		$tpl->clearAssign('params');
		$tpl->clearAssign('namePrefix');
		$tpl->clearAssign('token_labels');
	}
	
	function simulateActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$out = null;
		
		switch($token) {
			case 'exec_jquery':
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				$script = $tpl_builder->build($params['jquery_script'], $dict);
				
				$out = sprintf(">>> Executing jQuery script:\n\n%s\n",
					$script
				);
				break;
		}
		
		return $out;
	}
	
	function runActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		switch($token) {
			case 'exec_jquery':
				// Return the parsed script to the caller
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				$dict->_caller_actions['jquery_scripts'][] = $tpl_builder->build($params['jquery_script'], $dict);
				break;
		}
	}
};