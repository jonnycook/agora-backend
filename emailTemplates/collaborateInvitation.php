<?php

function emailTemplate_collaborateInvitation($params) {
	ob_start();
	$imageRoot = "http://agora.sh/images/email/collaborateInvitation1/";
	$textStyles = 'color:#373737; font-family:helvetica';
?>

<table cellspacing="0" cellpadding="0" border="0" width="100%" style="border-spacing:0">
	<tr>
		<td style="background-color:#EAAD2B; height:64px; padding-left:14px">
			<img src="<?php echo $imageRoot ?>logo.png" width="28" height="30">
		</td>
	</tr>
	<tr>
		<td style="height:200px" align="center">
			<img width="187" height="104" src="<?php echo $imageRoot ?>collaborateSymbol.png">
		</td>
	</tr>
	<tr>
		<td>
			<?php if ($params['message']): ?>
			<?php if ($params['title']): ?>
			<div style="margin-left:33px; margin-bottom:3px; font-size:22px; font-weight:bold; <?php echo $textStyles ?>"><?php echo $params['title'] ?></div>
			<?php endif ?>
			<table cellspacing="0" cellpadding="0" border="0" style="table-layout:fixed; border-spacing:0" width="100%">
				<tr>
					<td width="3" height="3"><img style="display:block" src="<?php echo $imageRoot ?>tl.png" width="3" height="3"></td>
					<td height="3" style="background-color:#D8D8D8"></td>
					<td width="3" height="3"><img style="display:block" src="<?php echo $imageRoot ?>tr.png" width="3" height="3"></td>
				</tr>
				<tr>
					<td style="background-color:#D8D8D8"></td>
					<td style="background-color:#D8D8D8"><div style="margin:30px; <?php echo $textStyles ?>; font-size:16px"><?php echo $params['message'] ?></div></td>
					<td style="background-color:#D8D8D8"></td>
				</tr>
				<tr>
					<td width="3" height="3"><img style="display:block" src="<?php echo $imageRoot ?>bl.png" width="3" height="3"></td>
					<td style="background-color:#D8D8D8"></td>
					<td width="3" height="3"><img style="display:block" src="<?php echo $imageRoot ?>br.png" width="3" height="3"></td>
				</tr>
				<tr>
					<td style="padding-left:35px" colspan="3">
						<table cellspacing="0" cellpadding="0" border="0" style="table-layout:fixed;">
							<tr>
								<td width="27"><img width="27" height="28" src="<?php echo $imageRoot ?>connector.png"></td>
								<td style="padding-left:12px; <?php echo $textStyles ?>; font-size: 22px; font-style:italic"><?php echo $params['name'] ?></td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
			<?php endif ?>
		</td>
	</tr>
	<tr>
		<td align="center" style="height:175px">
			<a href="<?php echo $params['url'] ?>"><img src="<?php echo $imageRoot ?>collaborateButton.png" width="320" height="76"></a>
		</td>
	</tr>
</table>

<?php
$email = ob_get_contents();
ob_end_clean();
return $email;
}