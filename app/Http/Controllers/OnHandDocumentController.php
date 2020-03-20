<?php

namespace App\Http\Controllers;

use App\OnHandDocument;

use App\Document;

use App\GroupUser;

use Illuminate\Http\Request;

class OnHandDocumentController extends Controller
{
    
	public static function handleCompanyDocument($action, $model) {
		$groupId = $model->group_id;

		if( $groupId ) {
			$documentId = $model->document_id;

			$document = Document::findOrfail($documentId);

			if( $document->is_company_document == 1 ) {
				$groupMembers = GroupUser::select(['user_id'])->where('group_id', $groupId)->get();

				foreach( $groupMembers as $groupMember ) {
					if( $action == 'created' ) {
						OnHandDocument::firstOrCreate([
			        		'client_id' => $groupMember->user_id,
			        		'group_id' => $groupId,
			        		'document_id' => $document->id
			        	]);
					} elseif( $action == 'deleted' ) {
						OnHandDocument::where('client_id', $groupMember->user_id)
			        		->where('group_id', $groupId)
			        		->where('document_id', $document->id)
			        		->delete();
					}
				}
			}
		}
	}

	public static function feedCompanyDocument($action, $model) {
		$groupId = $model->group_id;
		$clientId = $model->user_id;

		if( $groupId ) {
			if( $action == 'created' ) {
				$companyDocuments = OnHandDocument::select(['document_id'])
					->where('group_id', $groupId)
					->whereHas('document' , function($query) {
						$query->where('is_company_document', 1);
					})
					->get();

				foreach( $companyDocuments as $companyDocument ) {
					OnHandDocument::firstOrCreate([
			        	'client_id' => $clientId,
			        	'group_id' => $groupId,
			        	'document_id' => $companyDocument->document_id
			        ]);
				}
			} elseif( $action == 'deleted' ) {
				OnHandDocument::where('client_id', $clientId)
			        ->where('group_id', $groupId)
			        ->delete();
			}
		}
	}

}
