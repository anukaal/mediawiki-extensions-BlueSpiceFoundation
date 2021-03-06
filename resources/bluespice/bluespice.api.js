/*
 * Implementation for bs.api
 */

( function ( mw, bs, $, undefined ) {

	/**
	 * e.g. bs.api.tasks.execSilent(...).done(...);
	 * @param string module
	 * @param string taskname
	 * @param object data
	 * @param object cfg
	 * @returns jQuery.Promise
	 */
	function _execTaskSilent( module, task, data, cfg ) {
		cfg = cfg || {};
		cfg = $.extend( {
			success: function( response, module, task, $dfd, cfg ) {
				$dfd.resolve( response );
			},
			failure: function( response, module, task, $dfd, cfg ) {
				$dfd.resolve( response );
			},
			loadingIndicator: false
		}, cfg );

		return _execTask( module, task, data, cfg );
	}
	/**
	 * e.g. bs.api.tasks.exec(
			'wikipage',
			'setCategories',
			{ categories: [ 'C1', 'C2' ] }
		)
		.done(...);
	 * @param string module
	 * @param string taskname
	 * @param object data
	 * @param object cfg - set { useService: true } to use new task service
	 * @returns jQuery.Promise
	 */
	function _execTask( module, task, data, cfg ) {
		cfg = cfg || {};
		cfg = $.extend( {
			token: 'csrf',
			context: {},
			success: _msgSuccess,
			failure: _msgFailure,
			loadingIndicator: true
		}, cfg );

		var $dfd = $.Deferred();
		if ( cfg.loadingIndicator ) {
			bs.loadIndicator.pushPending();
		}

		var api = new mw.Api();
		api.postWithToken(cfg.token, {
			action: cfg.useService ? 'bs-task' : 'bs-' + module + '-tasks',
			task: task,
			taskData: JSON.stringify(data),
			context: JSON.stringify(
				$.extend(
					_getContext(),
					cfg.context
				)
			)
		})
			.done(function (response) {
				if ( cfg.loadingIndicator ) {
					bs.loadIndicator.popPending();
				}
				if (response.success === true) {
					cfg.success(response, module, task, $dfd, cfg);
				} else {
					cfg.failure(response, module, task, $dfd, cfg);
				}
			})
			.fail(function (code, result) { //Server error like FATAL
				if ( cfg.loadingIndicator ) {
					bs.loadIndicator.popPending();
				}
				if (result.exception) {
					result = {
						success: false,
						message: result.exception,
						errors: [{
							message: code
						}]
					};
				}
				cfg.failure(result, module, task, $dfd, cfg);
			});
		return $dfd.promise();
	}

	/**
	 * e.g. bs.api.store.getData(
			'groups'
		)
		.done(...);
	 * @param string module
	 * @param object cfg
	 * @returns jQuery.Promise
	 */
	function _getStoreData( module, cfg ) {
		cfg = cfg || {};
		cfg = $.extend( {
			token: 'csrf',
			context: {},
			loadingIndicator: true
		}, cfg );

		var $dfd = $.Deferred();
		if ( cfg.loadingIndicator ) {
			bs.loadIndicator.pushPending();
		}

		var api = new mw.Api();
		api.postWithToken( cfg.token, {
			action: 'bs-'+ module +'-store',
			context: JSON.stringify(
				$.extend (
					_getContext(),
					cfg.context
				)
			)
		})
		.done(function( response ){
			if ( cfg.loadingIndicator ) {
				bs.loadIndicator.popPending();
			}
			$dfd.resolve( response );
		})
		.fail( function( code, errResp ) { //Server error like FATAL
			if ( cfg.loadingIndicator ) {
				bs.loadIndicator.popPending();
			}
			$dfd.resolve( errResp );
		});
		return $dfd.promise();
	}

	function _msgSuccess( response, module, task, $dfd, cfg ) {
		if ( response.message.length ) {
			mw.notify( response.message, { title: mw.msg( 'bs-extjs-title-success' ) } );
			$dfd.resolve( response );
		}
		else {
			$dfd.resolve( response );
		}
	}

	function _msgFailure( response, module, task, $dfd, cfg ) {
		var message = response.message || '';
		if ( response.errors && response.errors.length > 0 ) {
			for ( var i in response.errors ) {
				if ( typeof( response.errors[i].html ) === 'string' ) {
					message = message + '<br />' + response.errors[i].html;
					continue;
				}
				if ( typeof( response.errors[i].plaintext ) === 'string' ) {
					message = message + "\n" + response.errors[i].plaintext;
					continue;
				}
				if ( typeof( response.errors[i].wiki ) === 'string' ) {
					message = message + "\n*" + response.errors[i].wiki;
					continue;
				}
				if ( typeof( response.errors[i].message ) === 'string' ) {
					message = message + '<br />' + response.errors[i].message;
					continue;
				}
				if ( typeof( response.errors[i].code ) === 'string' ) {
					message = message + '<br />' + response.errors[i].code;
					continue;
				}
			}
		}
		if ( message.length ) {
			bs.util.alert(
				module + '-' + task + '-fail',
				{
					titleMsg: 'bs-extjs-title-warning',
					text: message
				},
				{
					ok: function() {
						$dfd.reject( response );
					}
				}
			);
		}
		else {
			$dfd.reject( response );
		}
	}

	function _makeTaskUrl( module, task, data, additionalParams ) {

		var params = $.extend( {
			task: task,
			taskData: JSON.stringify( data ),
			token: mw.user.tokens.get( 'csrfToken' )
		}, additionalParams );

		return _makeUrl(
			'bs-'+ module +'-tasks',
			params,
			true
		);
	}

	function _makeUrl( action, params, sendContext ) {
		var baseParams = {
			'action': action
		};

		if ( sendContext ) {
			baseParams.context = JSON.stringify( _getContext() );
		}

		var script = mw.util.wikiScript( 'api' );
		var callParams = params || {};

		return script + "?" + $.param(
			$.extend( baseParams, callParams )
		);
	}

	function _getContext() {
		//HINT: https://www.mediawiki.org/wiki/Manual:Interface/JavaScript
		//Sync with serverside implementation of 'BSExtendedApiContext::newFromRequest'
		return {
			wgAction: mw.config.get( 'wgAction' ),
			wgArticleId: mw.config.get( 'wgArticleId' ),
			wgCanonicalNamespace: mw.config.get( 'wgCanonicalNamespace' ),
			wgCanonicalSpecialPageName: mw.config.get( 'wgCanonicalSpecialPageName' ),
			wgRevisionId: mw.config.get( 'wgRevisionId' ),
			//wgIsArticle: mw.config.get('wgIsArticle'),
			wgNamespaceNumber: mw.config.get( 'wgNamespaceNumber' ),
			wgPageName: mw.config.get( 'wgPageName' ),
			wgRedirectedFrom: mw.config.get( 'wgRedirectedFrom' ), //maybe null
			wgRelevantPageName: mw.config.get( 'wgRelevantPageName' ),
			wgTitle: mw.config.get( 'wgTitle' )
		};
	}

	bs.api = {
		tasks: {
			exec: _execTask,
			execSilent: _execTaskSilent,
			makeUrl: _makeTaskUrl
		},
		store: {
			getData: _getStoreData
		},
		makeUrl: _makeUrl
	};

}( mediaWiki, blueSpice, jQuery ) );
