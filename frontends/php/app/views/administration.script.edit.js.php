<script type="text/javascript">
	jQuery(document).ready(function() {
		// type change
		jQuery('#type').change(function() {
			var type = jQuery(this).val(),
				commandIpmi = jQuery('#commandipmi'),
				command = jQuery('#command');

			if (type == <?= ZBX_SCRIPT_TYPE_IPMI ?>) {
				commandIpmi.closest('li').show();

				if (command.val() != '') {
					commandIpmi.val(command.val());
					command.val('');
				}

				jQuery('#execute_on').add(command).closest('li').hide();
			}
			else {
				jQuery('#execute_on').add(command).closest('li').show();

				if (commandIpmi.val() != '') {
					command.val(commandIpmi.val());
					commandIpmi.val('');
				}

				commandIpmi.closest('li').hide();
			}
		})
			.trigger('change');

		// clone button
		jQuery('#clone').click(function() {
			jQuery('#scriptid, #delete, #clone').remove();
			jQuery('#update').text(<?= CJs::encodeJson(_('Add')) ?>);
			jQuery('#update').val('script.create').attr({id: 'add'});
			jQuery('#name').focus();
		});

		// confirmation text input
		jQuery('#confirmation').keyup(function() {
			jQuery('#testConfirmation').prop('disabled', (this.value == ''));
		}).keyup();

		// enable confirmation checkbox
		jQuery('#enable_confirmation').change(function() {
			if (this.checked) {
				jQuery('#confirmation').prop('readonly', false).keyup();
			}
			else {
				jQuery('#testConfirmation').prop('disabled', true);
				jQuery('#confirmation').prop('readonly', true);
			}
		}).change();

		// test confirmation button
		jQuery('#testConfirmation').click(function() {
			executeScript(null, null, jQuery('#confirmation').val());
		});

		// host group selection
		jQuery('#hgstype')
			.change(function() {
				if (jQuery('#hgstype').val() == 1) {
					jQuery('#hostGroupSelection').show();
				}
				else {
					jQuery('#hostGroupSelection').hide();
				}
			})
			.trigger('change');

		// trim spaces on sumbit
		jQuery('#scriptForm').submit(function() {
			jQuery(this).trimValues(['#name', '#command', '#description']);
		});
	});
</script>
