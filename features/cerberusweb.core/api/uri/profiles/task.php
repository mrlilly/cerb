<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2016, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.io/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.io	    http://webgroup.media
***********************************************************************/

class PageSection_ProfilesTask extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$translate = DevblocksPlatform::getTranslationService();
		$response = DevblocksPlatform::getHttpResponse();
		$active_worker = CerberusApplication::getActiveWorker();

		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // task
		@$id = intval(array_shift($stack));
		
		if(null != ($task = DAO_Task::get($id))) {
			$tpl->assign('task', $task);
		}
		
		$point = 'core.page.tasks';
		$tpl->assign('point', $point);
		
		// Properties
		
		$properties = array();
		
		$properties['status'] = array(
			'label' => mb_ucfirst($translate->_('common.status')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => null,
		);
		
		if(!$task->is_completed) {
			$properties['due_date'] = array(
				'label' => mb_ucfirst($translate->_('task.due_date')),
				'type' => Model_CustomField::TYPE_DATE,
				'value' => $task->due_date,
			);
			
		} else {
			$properties['completed_date'] = array(
				'label' => mb_ucfirst($translate->_('task.completed_date')),
				'type' => Model_CustomField::TYPE_DATE,
				'value' => $task->completed_date,
			);
		}
		
		$properties['created_at'] = array(
			'label' => mb_ucfirst($translate->_('common.created')),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $task->created_at,
		);
		
		$properties['updated_date'] = array(
			'label' => mb_ucfirst($translate->_('common.updated')),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $task->updated_date,
		);
		
		// Custom Fields

		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_TASK, $task->id)) or array();
		$tpl->assign('custom_field_values', $values);
		
		$properties_cfields = Page_Profiles::getProfilePropertiesCustomFields(CerberusContexts::CONTEXT_TASK, $values);
		
		if(!empty($properties_cfields))
			$properties = array_merge($properties, $properties_cfields);
		
		// Custom Fieldsets

		$properties_custom_fieldsets = Page_Profiles::getProfilePropertiesCustomFieldsets(CerberusContexts::CONTEXT_TASK, $task->id, $values);
		$tpl->assign('properties_custom_fieldsets', $properties_custom_fieldsets);
		
		// Link counts
		
		$properties_links = array(
			CerberusContexts::CONTEXT_TASK => array(
				$task->id => 
					DAO_ContextLink::getContextLinkCounts(
						CerberusContexts::CONTEXT_TASK,
						$task->id,
						array(CerberusContexts::CONTEXT_WORKER, CerberusContexts::CONTEXT_CUSTOM_FIELDSET)
					),
			),
		);
		
		$tpl->assign('properties_links', $properties_links);
		
		// Properties
		
		$tpl->assign('properties', $properties);
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		// Macros
		
		$macros = DAO_TriggerEvent::getReadableByActor(
			$active_worker,
			'event.macro.task'
		);
		$tpl->assign('macros', $macros);
		
		// Tabs
		$tab_manifests = Extension_ContextProfileTab::getExtensions(false, CerberusContexts::CONTEXT_TASK);
		$tpl->assign('tab_manifests', $tab_manifests);
		
		// Template
		$tpl->display('devblocks:cerberusweb.core::profiles/task.tpl');
	}
	
	function savePeekJsonAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer','');
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer',0);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		header('Content-Type: application/json; charset=' . LANG_CHARSET_CODE);
		
		try {
			if(!empty($id) && !empty($delete)) { // delete
				if(!$active_worker->hasPriv('core.tasks.actions.delete'))
					throw new Exception_DevblocksAjaxValidationError("You don't have permission to delete this record.");
				
				DAO_Task::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else { // create/edit
			
				// Load the existing model so we can detect changes
				if($id && false == ($task = DAO_Task::get($id)))
					throw new Exception_DevblocksAjaxValidationError("There was an unexpected error when loading this record.");
				
				$fields = array();
	
				// Title
				@$title = DevblocksPlatform::importGPC($_REQUEST['title'],'string','');
				
				if(empty($title))
					throw new Exception_DevblocksAjaxValidationError("The 'title' field is required.", 'title');
				
				$fields[DAO_Task::TITLE] = $title;
				
				// Completed
				@$completed = DevblocksPlatform::importGPC($_REQUEST['completed'],'integer',0);
				
				$fields[DAO_Task::IS_COMPLETED] = intval($completed);
				
				// [TODO] This shouldn't constantly update the completed date (it should compare)
				if($completed)
					$fields[DAO_Task::COMPLETED_DATE] = time();
				else
					$fields[DAO_Task::COMPLETED_DATE] = 0;
				
				// Updated Date
				$fields[DAO_Task::UPDATED_DATE] = time();
				
				// Due Date
				@$due_date = DevblocksPlatform::importGPC($_REQUEST['due_date'],'string','');
				@$fields[DAO_Task::DUE_DATE] = empty($due_date) ? 0 : intval(strtotime($due_date));
		
				// Importance
				@$importance = DevblocksPlatform::importGPC($_REQUEST['importance'],'integer',0);
				$fields[DAO_Task::IMPORTANCE] = $importance;
				
				// Owner
				@$owner_id = DevblocksPlatform::importGPC($_REQUEST['owner_id'],'integer',0);
				$fields[DAO_Task::OWNER_ID] = $owner_id;
		
				// Comment
				@$comment = DevblocksPlatform::importGPC($_REQUEST['comment'],'string','');
	
				// Custom Fields
				@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', array());
				
				// Save
				if(!empty($id)) {
					DAO_Task::update($id, $fields);
					DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_TASK, $id, $field_ids);
					
				} else {
					$custom_fields = DAO_CustomFieldValue::parseFormPost(CerberusContexts::CONTEXT_TASK, $field_ids);
					
					if(false == ($id = DAO_Task::create($fields, $custom_fields)))
						return false;
	
					// Context Link (if given)
					@$link_context = DevblocksPlatform::importGPC($_REQUEST['link_context'],'string','');
					@$link_context_id = DevblocksPlatform::importGPC($_REQUEST['link_context_id'],'integer','');
					if(!empty($id) && !empty($link_context) && !empty($link_context_id)) {
						DAO_ContextLink::setLink(CerberusContexts::CONTEXT_TASK, $id, $link_context, $link_context_id);
					}
					
					// View marquee
					if(!empty($id) && !empty($view_id)) {
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_TASK, $id);
					}
				}
	
				// Comments
				if(!empty($comment) && !empty($id)) {
					$also_notify_worker_ids = array_keys(CerberusApplication::getWorkersByAtMentionsText($comment));
					
					$fields = array(
						DAO_Comment::CONTEXT => CerberusContexts::CONTEXT_TASK,
						DAO_Comment::CONTEXT_ID => $id,
						DAO_Comment::OWNER_CONTEXT => CerberusContexts::CONTEXT_WORKER,
						DAO_Comment::OWNER_CONTEXT_ID => $active_worker->id,
						DAO_Comment::CREATED => time(),
						DAO_Comment::COMMENT => $comment,
					);
					$comment_id = DAO_Comment::create($fields, $also_notify_worker_ids);
				}
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'label' => $title,
					'view_id' => $view_id,
				));
				return;
			}
			
		} catch (Exception_DevblocksAjaxValidationError $e) {
			echo json_encode(array(
				'status' => false,
				'error' => $e->getMessage(),
				'field' => $e->getFieldName(),
			));
			return;
			
		} catch (Exception $e) {
			echo json_encode(array(
				'status' => false,
				'error' => 'An error occurred.',
			));
			return;
			
		}
	}
	
	function showBulkPopupAction() {
		@$ids = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);

		$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);

		if(!empty($ids)) {
			$id_list = DevblocksPlatform::parseCsvString($ids);
			$tpl->assign('ids', implode(',', $id_list));
		}
		
		$workers = DAO_Worker::getAllActive();
		$tpl->assign('workers', $workers);
		
		// Custom Fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_TASK, false);
		$tpl->assign('custom_fields', $custom_fields);
		
		// Macros
		
		$macros = DAO_TriggerEvent::getReadableByActor(
			$active_worker,
			'event.macro.task'
		);
		$tpl->assign('macros', $macros);
		
		$tpl->display('devblocks:cerberusweb.core::tasks/rpc/bulk.tpl');
	}
	
	function startBulkUpdateJsonAction() {
		// Filter: whole list or check
		@$filter = DevblocksPlatform::importGPC($_REQUEST['filter'],'string','');
		$ids = array();
		
		// View
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);
		
		// Task fields
		@$due = trim(DevblocksPlatform::importGPC($_POST['due'],'string',''));
		@$status = trim(DevblocksPlatform::importGPC($_POST['status'],'string',''));
		@$owner = trim(DevblocksPlatform::importGPC($_POST['owner'],'string',''));

		// Scheduled behavior
		@$behavior_id = DevblocksPlatform::importGPC($_POST['behavior_id'],'string','');
		@$behavior_when = DevblocksPlatform::importGPC($_POST['behavior_when'],'string','');
		@$behavior_params = DevblocksPlatform::importGPC($_POST['behavior_params'],'array',array());
		
		$do = array();
		$active_worker = CerberusApplication::getActiveWorker();
		
		// Do: Due
		if(0 != strlen($due))
			$do['due'] = $due;
			
		// Do: Status
		if(0 != strlen($status)) {
			switch($status) {
				case 2: // deleted
					if($active_worker->hasPriv('core.tasks.actions.delete'))
						$do['delete'] = true;
					break;
				default:
					$do['status'] = $status;
					break;
			}
		}
		
		// Do: Owner
		if(0 != strlen($owner))
			$do['owner'] = intval($owner);
		
		// Do: Scheduled Behavior
		if(0 != strlen($behavior_id)) {
			$do['behavior'] = array(
				'id' => $behavior_id,
				'when' => $behavior_when,
				'params' => $behavior_params,
			);
		}
		
		// Watchers
		$watcher_params = array();
		
		@$watcher_add_ids = DevblocksPlatform::importGPC($_REQUEST['do_watcher_add_ids'],'array',array());
		if(!empty($watcher_add_ids))
			$watcher_params['add'] = $watcher_add_ids;
			
		@$watcher_remove_ids = DevblocksPlatform::importGPC($_REQUEST['do_watcher_remove_ids'],'array',array());
		if(!empty($watcher_remove_ids))
			$watcher_params['remove'] = $watcher_remove_ids;
		
		if(!empty($watcher_params))
			$do['watchers'] = $watcher_params;
			
		// Do: Custom fields
		$do = DAO_CustomFieldValue::handleBulkPost($do);

		switch($filter) {
			// Checked rows
			case 'checks':
				@$ids_str = DevblocksPlatform::importGPC($_REQUEST['ids'],'string');
				$ids = DevblocksPlatform::parseCsvString($ids_str);
				break;
				
			case 'sample':
				@$sample_size = min(DevblocksPlatform::importGPC($_REQUEST['filter_sample_size'],'integer',0),9999);
				$filter = 'checks';
				$ids = $view->getDataSample($sample_size);
				break;
				
			default:
				break;
		}
		
		// If we have specific IDs, add a filter for those too
		if(!empty($ids)) {
			$view->addParam(new DevblocksSearchCriteria(SearchFields_Task::ID, 'in', $ids));
		}
		
		// Create batches
		$batch_key = DAO_ContextBulkUpdate::createFromView($view, $do);
		
		header('Content-Type: application/json; charset=utf-8');
		
		echo json_encode(array(
			'cursor' => $batch_key,
		));
		
		return;
	}
	
	function viewMarkCompletedAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		@$row_ids = DevblocksPlatform::importGPC($_REQUEST['row_id'],'array',array());

		try {
			if(is_array($row_ids))
			foreach($row_ids as $row_id) {
				$row_id = intval($row_id);
				
				if(!empty($row_id))
					DAO_Task::update($row_id, array(
						DAO_Task::IS_COMPLETED => 1,
						DAO_Task::COMPLETED_DATE => time(),
					));
			}
		} catch (Exception $e) {
			//
		}
		
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->render();
		exit;
	}
	
	function viewTasksExploreAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$url_writer = DevblocksPlatform::getUrlService();
		
		// Generate hash
		$hash = md5($view_id.$active_worker->id.time());
		
		// Loop through view and get IDs
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);

		// Page start
		@$explore_from = DevblocksPlatform::importGPC($_REQUEST['explore_from'],'integer',0);
		if(empty($explore_from)) {
			$orig_pos = 1+($view->renderPage * $view->renderLimit);
		} else {
			$orig_pos = 1;
		}
		
		$view->renderPage = 0;
		$view->renderLimit = 250;
		$pos = 0;
		
		do {
			$models = array();
			list($results, $total) = $view->getData();

			// Summary row
			if(0==$view->renderPage) {
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'title' => $view->name,
					'created' => time(),
					'total' => $total,
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=task', true),
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $task_id => $row) {
				if($task_id==$explore_from)
					$orig_pos = $pos;
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_Task::ID],
					'url' => $url_writer->writeNoProxy(sprintf("c=profiles&type=task&id=%d", $row[SearchFields_Task::ID]), true),
				);
				$models[] = $model;
			}
			
			DAO_ExplorerSet::createFromModels($models);
			
			$view->renderPage++;
			
		} while(!empty($results));
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('explore',$hash,$orig_pos)));
	}
	
};