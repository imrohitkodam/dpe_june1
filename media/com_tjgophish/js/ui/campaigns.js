var TjGoPhishCampaigns = {
	concludeCampaign: function (cid){
		var confirmMessage = Joomla.JText._("COM_TJGOPHISH_CAMPAIGNS_CAMPAIGN_CONCLUDE_CONFIRMATION");

		if (!confirm(confirmMessage))
		{
			return false;
		}
		else
		{
			jQuery('input[name ="ccid"]').val(cid);
			Joomla.submitform('campaign.conclude', document.getElementById('adminForm'));
		}
	}
}
