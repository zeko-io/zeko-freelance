/**
 * Zeko Freelance front-end JS: project form submit, bookmarking and
 * project deletion.
 */
(function ($) {
	'use strict';

	var cfg = window.ZekoFreelance || {};

	function post(action, data, done) {
		var payload = new FormData();
		payload.append('action', action);
		payload.append('nonce', cfg.nonce || '');
		if (data instanceof FormData) {
			data.forEach(function (value, key) {
				payload.append(key, value);
			});
		} else {
			Object.keys(data).forEach(function (key) {
				payload.append(key, data[key]);
			});
		}
		$.ajax({
			url: cfg.ajaxUrl,
			type: 'POST',
			data: payload,
			processData: false,
			contentType: false
		}).done(function (res) {
			done(null, res);
		}).fail(function (xhr) {
			var body = {};
			try {
				body = JSON.parse(xhr.responseText);
			} catch (e) {
				body = {};
			}
			done(body, null);
		});
	}

	// ---------------------------------------------------------------
	// Project form
	// ---------------------------------------------------------------
	$(document).on('submit', '#zf-project-form', function (e) {
		e.preventDefault();

		var $form = $(this);
		var $notice = $('#zf-form-notice');
		var $submit = $form.find('button[type="submit"]');
		var original = $submit.text();

		$submit.prop('disabled', true).text(cfg.i18n ? cfg.i18n.saving : 'Saving…');
		$notice.removeClass('zf-notice-error').text('');

		var data = new FormData($form[0]);
		var skills = [];
		$form.find('input[name="zf_skills[]"]:checked').each(function () {
			skills.push(this.value);
		});
		var extra = String($form.find('#zf-skills-extra').val() || '').split(',');
		extra.forEach(function (name) {
			name = $.trim(name);
			if (name && skills.indexOf(name) === -1) {
				skills.push(name);
			}
		});
		data.set('zf_skills', skills.join(','));

		post('zeko_freelance_save_project', data, function (err, res) {
			$submit.prop('disabled', false).text(original);

			if (err || !res || res.error) {
				$notice.addClass('zf-notice-error').text((err && err.error) || (res && res.error) || 'Error');
				return;
			}

			if (res.project_id && res.status === 'open') {
				window.location.href = cfg.postUrl + (cfg.postUrl.indexOf('?') !== -1 ? '&' : '?') + 'zf_pid=' + res.project_id;
				return;
			}

			$notice.addClass('zf-notice-success').text(res.message || 'Saved');
		});
	});

	// ---------------------------------------------------------------
	// Bookmarks
	// ---------------------------------------------------------------
	$(document).on('click', '.zf-bookmark-btn', function (e) {
		e.preventDefault();

		var $btn = $(this);
		var projectId = $btn.data('project-id');
		var bookmarked = $btn.data('bookmarked') === '1';

		post('zeko_freelance_bookmark_project', { zf_project_id: projectId }, function (err, res) {
			if (err || !res || res.error) {
				alert((err && err.error) || (res && res.error) || 'Error');
				return;
			}

			if (res.bookmarked) {
				$btn.addClass('zf-active')
					.attr('aria-pressed', 'true')
					.data('bookmarked', '1')
					.text(cfg.i18n ? cfg.i18n.bookmarked : 'Bookmarked');
			} else {
				$btn.removeClass('zf-active')
					.attr('aria-pressed', 'false')
					.data('bookmarked', '0')
					.text(cfg.i18n ? cfg.i18n.bookmark : 'Bookmark');
			}
		});
	});

	// ---------------------------------------------------------------
	// Delete project
	// ---------------------------------------------------------------
	$(document).on('click', '.zf-delete-project', function (e) {
		e.preventDefault();

		var $btn = $(this);
		var projectId = $btn.data('project-id');
		var title = $btn.data('title') || '';

		if (!window.confirm('Delete "' + title + '" permanently?')) {
			return;
		}

		$btn.prop('disabled', true);
		post('zeko_freelance_delete_project', { zf_project_id: projectId }, function (err, res) {
			if (err || !res || res.error) {
				alert((err && err.error) || (res && res.error) || 'Error');
				$btn.prop('disabled', false);
				return;
			}
			window.location.reload();
		});
	});
	// ---------------------------------------------------------------
	// Bids
	// ---------------------------------------------------------------
	$(document).on('submit', '#zf-bid-form', function (e) {
		e.preventDefault();

		var $form = $(this);
		var $notice = $('#zf-bid-notice');
		var $submit = $form.find('button[type="submit"]');
		var original = $submit.text();

		$submit.prop('disabled', true).text(cfg.i18n ? (cfg.i18n.placing || 'Placing bid…') : 'Placing bid…');
		$notice.removeClass('zf-notice-error').text('');

		post('zeko_freelance_submit_bid', new FormData($form[0]), function (err, res) {
			if (err || !res || res.error) {
				$submit.prop('disabled', false).text(original);
				$notice.addClass('zf-notice-error').text((err && err.error) || (res && res.error) || 'Error');
				return;
			}
			window.location.reload();
		});
	});

	$(document).on('click', '.zf-withdraw-bid', function (e) {
		e.preventDefault();

		var $btn = $(this);
		if (!window.confirm('Withdraw this bid?')) {
			return;
		}

		$btn.prop('disabled', true).text(cfg.i18n ? (cfg.i18n.withdrawing || 'Withdrawing…') : 'Withdrawing…');
		post('zeko_freelance_withdraw_bid', { zf_bid_id: $btn.data('bid-id') }, function (err, res) {
			if (err || !res || res.error) {
				alert((err && err.error) || (res && res.error) || 'Error');
				window.location.reload();
				return;
			}
			window.location.reload();
		});
	});

	$(document).on('click', '.zf-award-bid', function (e) {
		e.preventDefault();

		var $btn = $(this);
		if (!window.confirm('Accept this bid and create a contract?')) {
			return;
		}

		$btn.prop('disabled', true).text(cfg.i18n ? (cfg.i18n.awarding || 'Accepting…') : 'Accepting…');
		post('zeko_freelance_award_bid', { zf_bid_id: $btn.data('bid-id') }, function (err, res) {
			if (err || !res || res.error) {
				alert((err && err.error) || (res && res.error) || 'Error');
				window.location.reload();
				return;
			}
			window.location.reload();
		});
	});

	// ---------------------------------------------------------------
	// Milestones, contracts & disputes
	// ---------------------------------------------------------------
	$(document).on('submit', '#zf-milestone-form', function (e) {
		e.preventDefault();

		var $form = $(this);
		var $notice = $('#zf-milestone-notice');
		var $submit = $form.find('button[type="submit"]');
		var original = $submit.text();

		$submit.prop('disabled', true).text(cfg.i18n ? (cfg.i18n.saving || 'Saving…') : 'Saving…');
		$notice.removeClass('zf-notice-error').text('');

		post('zeko_freelance_create_milestone', new FormData($form[0]), function (err, res) {
			if (err || !res || res.error) {
				$submit.prop('disabled', false).text(original);
				$notice.addClass('zf-notice-error').text((err && err.error) || (res && res.error) || 'Error');
				return;
			}
			window.location.reload();
		});
	});

	$(document).on('click', '.zf-fund-milestone', function (e) {
		e.preventDefault();

		var $btn = $(this);
		if (!window.confirm('Fund this milestone from your escrow balance?')) {
			return;
		}

		$btn.prop('disabled', true).text(cfg.i18n ? (cfg.i18n.funding || 'Funding…') : 'Funding…');
		post('zeko_freelance_fund_milestone', { zf_milestone_id: $btn.data('milestone-id') }, function (err, res) {
			if (err || !res || res.error) {
				alert((err && err.error) || (res && res.error) || 'Error');
				window.location.reload();
				return;
			}
			window.location.reload();
		});
	});

	$(document).on('click', '.zf-submit-milestone', function (e) {
		e.preventDefault();

		var $btn = $(this);
		if (!window.confirm('Submit this milestone for client review?')) {
			return;
		}

		$btn.prop('disabled', true).text(cfg.i18n ? (cfg.i18n.submitting || 'Submitting…') : 'Submitting…');
		post('zeko_freelance_submit_milestone', { zf_milestone_id: $btn.data('milestone-id') }, function (err, res) {
			if (err || !res || res.error) {
				alert((err && err.error) || (res && res.error) || 'Error');
				window.location.reload();
				return;
			}
			window.location.reload();
		});
	});

	$(document).on('click', '.zf-approve-milestone', function (e) {
		e.preventDefault();

		var $btn = $(this);
		if (!window.confirm('Approve this milestone and release the escrowed payment?')) {
			return;
		}

		$btn.prop('disabled', true).text(cfg.i18n ? (cfg.i18n.approving || 'Approving…') : 'Approving…');
		post('zeko_freelance_approve_milestone', { zf_milestone_id: $btn.data('milestone-id') }, function (err, res) {
			if (err || !res || res.error) {
				alert((err && err.error) || (res && res.error) || 'Error');
				window.location.reload();
				return;
			}
			window.location.reload();
		});
	});

	$(document).on('click', '.zf-reject-milestone', function (e) {
		e.preventDefault();

		var $btn = $(this);
		if (!window.confirm('Send this milestone back to the freelancer for revisions?')) {
			return;
		}

		$btn.prop('disabled', true).text(cfg.i18n ? (cfg.i18n.rejecting || 'Sending back…') : 'Sending back…');
		post('zeko_freelance_reject_milestone', { zf_milestone_id: $btn.data('milestone-id') }, function (err, res) {
			if (err || !res || res.error) {
				alert((err && err.error) || (res && res.error) || 'Error');
				window.location.reload();
				return;
			}
			window.location.reload();
		});
	});

	$(document).on('click', '.zf-cancel-contract', function (e) {
		e.preventDefault();

		var $btn = $(this);
		if (!window.confirm('Cancel this contract? All funded milestones will be refunded to the client.')) {
			return;
		}

		$btn.prop('disabled', true).text(cfg.i18n ? (cfg.i18n.cancelling || 'Cancelling…') : 'Cancelling…');
		post('zeko_freelance_cancel_contract', { zf_contract_id: $btn.data('contract-id') }, function (err, res) {
			if (err || !res || res.error) {
				alert((err && err.error) || (res && res.error) || 'Error');
				window.location.reload();
				return;
			}
			window.location.reload();
		});
	});

	$(document).on('click', '.zf-open-dispute', function (e) {
		e.preventDefault();
		$('#zf-dispute-form').prop('hidden', false);
		$('#zf-dispute-form input[name="zf_dispute_subject"]').trigger('focus');
	});

	$(document).on('submit', '#zf-dispute-form', function (e) {
		e.preventDefault();

		var $form = $(this);
		var $notice = $('#zf-dispute-notice');
		var $submit = $form.find('button[type="submit"]');
		var original = $submit.text();

		$submit.prop('disabled', true).text(cfg.i18n ? (cfg.i18n.opening || 'Opening…') : 'Opening…');
		$notice.removeClass('zf-notice-error').text('');

		post('zeko_freelance_open_dispute', new FormData($form[0]), function (err, res) {
			if (err || !res || res.error) {
				$submit.prop('disabled', false).text(original);
				$notice.addClass('zf-notice-error').text((err && err.error) || (res && res.error) || 'Error');
				return;
			}
			window.location.reload();
		});
	});

	// ---------------------------------------------------------------
	// Portfolio items
	// ---------------------------------------------------------------
	$(document).on('submit', '#zf-portfolio-form', function (e) {
		e.preventDefault();

		var $form = $(this);
		var $notice = $('#zf-portfolio-notice');
		var $submit = $form.find('button[type="submit"]');
		var original = $submit.text();
		var editing = !!$form.find('input[name="zf_portfolio_id"]').val();

		$submit.prop('disabled', true).text(cfg.i18n ? (editing ? (cfg.i18n.updating || 'Updating…') : (cfg.i18n.adding || 'Adding…')) : 'Saving…');
		$notice.removeClass('zf-notice-error').text('');

		var data = new FormData($form[0]);
		var skills = ($form.find('input[name="zf_portfolio_skills"]').val() || '').split(',');
		var cleanSkills = [];
		skills.forEach(function (skill) {
			skill = $.trim(skill);
			if (skill) {
				cleanSkills.push(skill);
			}
		});
		data.set('zf_portfolio_skills', cleanSkills.join(','));

		post('zeko_freelance_save_portfolio', data, function (err, res) {
			if (err || !res || res.error) {
				$submit.prop('disabled', false).text(original);
				$notice.addClass('zf-notice-error').text((err && err.error) || (res && res.error) || 'Error');
				return;
			}
			window.location.reload();
		});
	});

	$(document).on('click', '.zf-delete-portfolio', function (e) {
		e.preventDefault();

		var $btn = $(this);
		var title = $btn.data('title') || '';

		if (!window.confirm('Delete portfolio item "' + title + '" permanently?')) {
			return;
		}

		$btn.prop('disabled', true).text(cfg.i18n ? (cfg.i18n.deleting || 'Deleting…') : 'Deleting…');
		post('zeko_freelance_delete_portfolio', { zf_portfolio_id: $btn.data('portfolio-id') }, function (err, res) {
			if (err || !res || res.error) {
				alert((err && err.error) || (res && res.error) || 'Error');
				window.location.reload();
				return;
			}
			window.location.reload();
		});
	});

	// ---------------------------------------------------------------
	// Profile
	// ---------------------------------------------------------------
	$(document).on('submit', '#zf-profile-form', function (e) {
		e.preventDefault();

		var $form = $(this);
		var $notice = $('#zf-profile-notice');
		var $submit = $form.find('button[type="submit"]');
		var original = $submit.text();

		$submit.prop('disabled', true).text(cfg.i18n ? (cfg.i18n.saving || 'Saving…') : 'Saving…');
		$notice.removeClass('zf-notice-error').text('');

		post('zeko_freelance_save_profile', new FormData($form[0]), function (err, res) {
			if (err || !res || res.error) {
				$submit.prop('disabled', false).text(original);
				$notice.addClass('zf-notice-error').text((err && err.error) || (res && res.error) || 'Error');
				return;
			}
			window.location.reload();
		});
	});

	// ---------------------------------------------------------------
	// Verification application
	// ---------------------------------------------------------------
	$(document).on('submit', '#zf-verification-form', function (e) {
		e.preventDefault();

		var $form = $(this);
		var $notice = $('#zf-verification-notice');
		var $submit = $form.find('button[type="submit"]');
		var original = $submit.text();

		$submit.prop('disabled', true).text(cfg.i18n ? (cfg.i18n.submitting || 'Submitting…') : 'Submitting…');
		$notice.removeClass('zf-notice-error').text('');

		post('zeko_freelance_apply_verification', new FormData($form[0]), function (err, res) {
			if (err || !res || res.error) {
				$submit.prop('disabled', false).text(original);
				$notice.addClass('zf-notice-error').text((err && err.error) || (res && res.error) || 'Error');
				return;
			}
			window.location.reload();
		});
	});

	// ---------------------------------------------------------------
	// Reviews
	// ---------------------------------------------------------------
	$(document).on('submit', '#zf-review-form', function (e) {
		e.preventDefault();

		var $form = $(this);
		var $notice = $('#zf-review-notice');
		var $submit = $form.find('button[type="submit"]');
		var original = $submit.text();

		$submit.prop('disabled', true).text(cfg.i18n ? (cfg.i18n.saving || 'Saving…') : 'Saving…');
		$notice.removeClass('zf-notice-error').text('');

		post('zeko_freelance_submit_review', new FormData($form[0]), function (err, res) {
			if (err || !res || res.error) {
				$submit.prop('disabled', false).text(original);
				$notice.addClass('zf-notice-error').text((err && err.error) || (res && res.error) || 'Error');
				return;
			}
			window.location.reload();
		});
	});
	// ---------------------------------------------------------------
	// Skill autocomplete
	// ---------------------------------------------------------------
	$('[data-zf-skill-suggest]').each(function () {
		var $input = $(this);
		var $list = $input.siblings('datalist');

		if (!$list.length) {
			$list = $('<datalist id="zf-skill-options"></datalist>').insertAfter($input);
		}

		var fill = function (names) {
			$list.empty();
			names.forEach(function (name) {
				$('<option>').attr('value', name).appendTo($list);
			});
		};

		var timer = null;
		$input.on('input', function () {
			clearTimeout(timer);
			timer = setTimeout(function () {
				post('zeko_freelance_suggest_skills', { term: $input.val() }, function (err, res) {
					if (err || !res || res.error || !res.skills) {
						return;
					}
					fill(res.skills);
				});
			}, 250);
		});
	});
})(jQuery);
