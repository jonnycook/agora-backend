
<?php
ob_start();
?>
<table cellspacing="0" cellpadding="0" align="center">
	<tr>
		<td align="center">
			<div style="width:584px; text-align:center;font-size:14px; font-family:Tahoma; color:#636465; text-transform: uppercase; letter-spacing:4px; margin-bottom:14px">Center for Leadership Performance<br>David Lynch Foundation</div>
			<table style="width:584px; border:1px solid #070808; padding: 8px;" cellpadding="0" cellspacing="0">
				<tr>
					<td><img src="http://jonnycook.com/clients/davidlynch/newsletters/norman_email_11-3-14/banner.png" style="display:block"></td>
				</tr>
				<tr>
					<td style="color:#83868c; font-size:18px; font-family:Tahoma; background-color: #e6eeff; padding: 0 42px; line-height:25px; letter-spacing:1px">
						<div style="border-bottom: 1px solid #327ed0; margin-bottom:20px; padding-bottom:20px">Dr. Rosenthal is an internationally renowned medical researcher, clinical professor of psychiatry at Georgetown University Medical School, and author of the New York Times bestseller "Transcendence" along with "Winter Blues" and "Gift of Adversity."</div>

						<div style="color:#1d3961; font-weight:bold; text-align:center; margin-bottom:5px">MONDAY, NOVEMBER 3, 2014</div>
						<div style="text-align:center; border-bottom: 1px solid #327ed0; margin-bottom:20px; padding-bottom:20px">Center for Leadership Performance<br>
						216 East 45th Street, New York &bull; Suite 1301<br>
						6:30 PM Reception &bull; 7 to 8 PM Conversation</div>

						<div style="color:#1d3961; font-weight:bold; text-align:center; margin-bottom:5px">THE PERFECT INTRODUCTION<br>FOR FRIENDS AND FAMILY</div>
						<div style="text-align:center; border-bottom: 1px solid #327ed0; margin-bottom:20px; padding-bottom:20px">Here is an excellent opportunity for you to learn more about your own practice of Transcendental Meditation from one of the world's leading medical experts and, at the same time, introduce your interested friends, family and colleagues to the scientifically verified benefits of this simple, effortless practice. </div>

						<div style="color:#1d3961; font-weight:bold; text-align:center; margin-bottom:5px">RSVP</div>
						<div style="text-align:center; border-bottom: 1px solid #327ed0; margin-bottom:20px; padding-bottom:20px">Please email or call by Friday, October 31,<br> Genevieve Kimberlin at <br>Genevieve@DavidLynchFoundation.org or 212-444-1185<br>
						By invitation only &bull; No cost</div>

						<div style="color:#1d3961; font-weight:bold; text-align:center; margin-bottom:5px">CO-HOSTS</div>
						<div style="text-align:center">Center for Leadership Performance: www.TMBusinessNYC.org<br>
						David Lynch Foundation: www.DavidLynchFoundation.org</div>
					</td>
				</tr>
				<tr>
					<td><img src="http://jonnycook.com/clients/davidlynch/newsletters/norman_email_11-3-14/footer.png" style="display:block"></td>
				</tr>
			</table>
		</td>
	</tr>
</table>
<?php

$body = ob_get_contents();
ob_end_clean();
echo $body;
// exit;

$ch = curl_init('http://ext.agora.sh/email.php');
curl_setopt_array($ch, array(
	CURLOPT_POSTFIELDS => array(
		'fromEmail' => 'test@jonnycook.com',
		'fromName' => 'Test',
		'to' => 'qubsoft@gmail.com, austin@davidlynchfoundation.org',
		'subject' => 'Test',
		'body' => $body
	)
));
curl_exec($ch);