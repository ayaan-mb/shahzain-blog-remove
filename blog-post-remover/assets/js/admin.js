(function ($) {
	'use strict';

	var $confirmationInput = $('#bpr-confirmation-input');
	var $removeButton = $('#bpr-remove-button');
	var $progress = $('#bpr-progress');

	var isRunning = false;

	function setProgressMessage(message, isError) {
		$progress.text(message || '');
		$progress.toggleClass('bpr-error', !!isError);
	}

	function updateButtonState() {
		if (isRunning) {
			$removeButton.prop('disabled', true);
			return;
		}

		var matches = $confirmationInput.val() === bprAdmin.confirmationPhrase;
		$removeButton.prop('disabled', !matches);
	}

	function request(action, data) {
		return $.ajax({
			url: bprAdmin.ajaxUrl,
			type: 'POST',
			dataType: 'json',
			data: $.extend(
				{
					action: action,
					nonce: bprAdmin.nonce
				},
				data || {}
			)
		});
	}

	function runDeletion(total, deleted) {
		request('bpr_delete_batch', { total: total, deleted: deleted })
			.done(function (response) {
				if (!response || !response.success || !response.data) {
					setProgressMessage(bprAdmin.i18n.error, true);
					isRunning = false;
					updateButtonState();
					return;
				}

				var data = response.data;
				var deletedCount = parseInt(data.deleted, 10) || 0;
				var totalCount = parseInt(data.total, 10) || total;
				var complete = !!data.complete;

				setProgressMessage('Deleted ' + deletedCount + ' of ' + totalCount + ' posts...');

				if (complete) {
					setProgressMessage(bprAdmin.i18n.complete, false);
					isRunning = false;
					$confirmationInput.val('');
					updateButtonState();
					return;
				}

				runDeletion(totalCount, deletedCount);
			})
			.fail(function (xhr) {
				var message = bprAdmin.i18n.error;
				if (xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
					message = xhr.responseJSON.data.message;
				}

				setProgressMessage(message, true);
				isRunning = false;
				updateButtonState();
			});
	}

	$confirmationInput.on('input', function () {
		updateButtonState();
	});

	$removeButton.on('click', function () {
		if (isRunning || $confirmationInput.val() !== bprAdmin.confirmationPhrase) {
			return;
		}

		isRunning = true;
		updateButtonState();
		setProgressMessage(bprAdmin.i18n.processing, false);

		request('bpr_start_deletion')
			.done(function (response) {
				if (!response || !response.success || !response.data) {
					setProgressMessage(bprAdmin.i18n.error, true);
					isRunning = false;
					updateButtonState();
					return;
				}

				var total = parseInt(response.data.total, 10) || 0;

				if (total <= 0) {
					setProgressMessage(bprAdmin.i18n.empty, false);
					isRunning = false;
					$confirmationInput.val('');
					updateButtonState();
					return;
				}

				runDeletion(total, 0);
			})
			.fail(function (xhr) {
				var message = bprAdmin.i18n.error;
				if (xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
					message = xhr.responseJSON.data.message;
				}

				setProgressMessage(message, true);
				isRunning = false;
				updateButtonState();
			});
	});

	updateButtonState();
})(jQuery);
