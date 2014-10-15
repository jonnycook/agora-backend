<?php

function emailTemplate_template($params) {
	ob_start();
	$imageRoot = "http://agora.sh/images/email/layout/";
	$textStyles = 'color:#373737; font-family:helvetica';
	$orange = '#EAAD2B';
	$shadowOrange = '#D29B26';
	$darkOrange = '#CB8A00';
	$shadow = '#E5E5E5';

	$darkGray = '#4D4D4D';

	$linkStyles = "text-decoration: none; color: $orange";
	$textStyles = "font-size:14px; line-height: 24px; font-family: 'Lucida Grande'; color: $darkGray";
?>

<table cellspacing="0" cellpadding="0" border="0" width="100%" style="border-spacing:0">
	<tr>
		<td style="height:231px; background-color:<?php echo $orange ?>"></td>
		<td style="width:512px" rowspan="2" valign="top">
			<table cellspacing="0" cellpadding="0" border="0" width="100%" style="table-layout:fixed; border-spacing:0">
				<tr>
					<td style="height:91px; width:6px; background-color:<?php echo $orange ?>"></td>
					<td valign="top" style="border-bottom: 4px solid <?php echo $shadow ?>; <?php echo $textStyles ?>" rowspan="4">
						<table cellspacing="0" cellpadding="0" border="0" width="100%" style="height:95px; border-spacing:0; background-color:<?php echo $orange ?>; border-bottom: 4px solid <?php echo $shadowOrange ?>;">
							<tr>
								<td style="height:48px" colspan="3"></td>
							</tr>
							<tr>
								<td style="width:105px">
									<a href="http://agora.sh"><img style="vertical-align:bottom; margin-right: 7px; display: block" src="<?php echo $imageRoot ?>logo.png"></a>
								</td>
								<td>
									<span style="color:<?php echo $darkOrange ?>; font-family: 'Lucida Grande'; font-size: 26px; vertical-align:bottom"><?php echo $params['title']?></span>
								</td>
								<td style="width:104px">
									<a href="https://twitter.com/agorawisdom" style="vertical-align:bottom; margin-right: 16px"><img src="<?php echo $imageRoot ?>twitter.png"></a><a style="vertical-align:bottom; margin-right: 16px" href="https://www.facebook.com/pages/Agora/290440127749028"><img src="<?php echo $imageRoot ?>facebook.png"></a><a href="https://plus.google.com/u/0/104439806048452341734"><img src="<?php echo $imageRoot ?>googlePlus.png"></a>
								</td>
							</tr>
						</table>

						<div>
							<?php $params['body'](array('linkStyles' => $linkStyles)) ?>
						</div>
					</td>
					<td style="height:91px; width:6px; background-color:<?php echo $orange ?>"></td>
				</tr>
				<tr>
					<td valign="top" style="height:140px"><img style="display: block" src="<?php echo $imageRoot ?>headerLeft.png"></td>
					<td valign="top" style="height:140px"><img style="display: block" src="<?php echo $imageRoot ?>headerRight.png"></td>
				</tr>
				<tr>
					<td style="height:100%; border-right:2px solid white; background-color:<?php echo $shadow ?>"></td>
					<td style="height:100%; border-left:2px solid white; background-color:<?php echo $shadow ?>"></td>
				</tr>
				<tr>
					<td valign="top" style="height:6px"><img style="display: block" src="<?php echo $imageRoot ?>footerLeft.png"></td>
					<td valign="top" style="height:6px"><img style="display: block" src="<?php echo $imageRoot ?>footerRight.png"></td>
				</tr>
				<tr>
					<td colspan="3" style="padding:38px; <?php echo $textStyles ?>">
						<p>You're receiving this email because you have an Agora account. If you don't want to receive these anymore, feel free to <a style="<?php echo $linkStyles ?>" href="#">unsubscribe</a>.</p>
						<p style="margin:0">&copy; Agora Labs, Inc. 2014</p>
						<p style="margin:0"><a style="<?php echo $linkStyles ?>" href="http://agora.sh/privacy.html">Privacy Policy</a></p>
					</td>
				</tr>
			</table>
		</td>
		<td style="height:231px; background-color:<?php echo $orange ?>"></td>
	</tr>
	<tr>
		<td style="height:100%"></td>
		<td style="height:100%"></td>
	</tr>
</table>
<?php
$email = ob_get_contents();
ob_end_clean();
return $email;
}