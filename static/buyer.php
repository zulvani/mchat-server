<?php

/*
http://localhost/mchat/static/buyer.php?productId=1&productName=Samsung&buyerId=2&buyerName=Saeful%20Anwar
http://localhost/mchat/static/buyer.php?productId=2&productName=Iphone&buyerId=3&buyerName=Muh%20Ridwan
*/

$buyerId = isset($_GET['buyerId']) ? $_GET['buyerId'] : 2;
$buyerName = isset($_GET['buyerName']) ? $_GET['buyerName'] : "Client - " . $buyerId;
$productId = isset($_GET['productId']) ? $_GET['productId'] : 1;
$productName = isset($_GET['productName']) ? $_GET['productName'] : "Product - " . $productId;
$sellerId = isset($_GET['sellerId']) ? $_GET['sellerId'] : 1;
$sellerName = isset($_GET['sellerName']) ? $_GET['sellerName'] : "Seller - " . $sellerId;
?>

<html>
	<head>
		<title>Buyer</title>
		<!-- // <script src="socket.io/socket.io.js"></script> -->
		<script src="http://code.jquery.com/jquery-1.11.1.js"></script>
		<script src="../libs/socket.io-client/socket.io.js"></script>
		<script src="../libs/mchat.js"></script>

		<script>
		var host = 'https://evo-chat.herokuapp.com';
		var socket = new io(host);

		$(document).ready(function(){
			var product = {'id': <?=$productId?>, 'title': '<?=$productName?>'};
			var buyer = {'id': <?=$buyerId?>, 'name': '<?=$buyerName?>', 'socketId': '', 'type': entityType.BUYER, 'product': product};
			var seller = {'id': <?=$sellerId?>, 'name': '<?=$sellerName?>', 'socketId': '', 'type': entityType.SELLER};

			socket.emit('isSellerOnline', seller);
			socket.emit('memberConnect', seller, buyer, 'buyer');

			socket.on('memberConnected', function(member){
				buyer.socketId = member.socketId;
			});

			socket.on('updateSellerStatus', function(online){
				if(online){
					$('#seller').text('Online');
					$('#seller-online-wrapper').show();
					$('#open-chat').show();
				}
				else{
					$('#seller').text('Offline');
					$('#seller-online-wrapper').hide();
					$('#open-chat').hide();
				}
			});

			socket.on('openChat', function(messages){
				for(i in messages){
					$('#chat-history > ul').append(
					'<li id="message-'+ messages[i].id +'">[' + messages[i].from.name + '] ' + messages[i].content + '</li>');
					socket.emit('chatMessageReceived', messages[i]);
				}

				if(messages != null && messages.length > 0){
					socket.emit('chatMessageRead', messages);
				}
			});

			socket.on('chatMessage', function(message){
				var d = new Date(message.sentByServerDate);
				var hour = d.getHours();
				var minute = d.getMinutes();
				var hm = hour + ':' + minute;

				$('#chat-history > ul').append(
					'<li class="chat-him" id="message-'+message.id+'">' + message.content + 
					'<div class="message-time">' + hm + '</div>' +
					'</li>');
				$('#message').val('');

				socket.emit('chatMessageReceived', message);

				var readMessages = [message];
				socket.emit('chatMessageRead', readMessages);

				var elem = document.getElementById('chat-history');
  				elem.scrollTop = elem.scrollHeight;

  				var ll = 8;
	  				var mWidth = ll * message.content.length;
	  				if(mWidth < 50)
	  					mWidth = 50;
	  				else if(mWidth > 180)
	  					mWidth = 180;

	  				$('#message-' + message.id).css('width', mWidth);
			});

			socket.on('chatMessageReceived', function(message){
				if(message.state === messageState.RECEIVED){
					$('#message-' + message.id).css('color', '#000');
				}
			});

			socket.on('chatMessageRead', function(messages){
				for(i in messages){
					var message = messages[i];
					if(message.state === messageState.READ){
						$('#read-message-' + message.id).show();
					}
				}
			});

			socket.on('typing', function(seller, buyer, from){
				$('#typing').text('typing...');
			});

			socket.on('stop-typing', function(seller, buyer, from){
				$('#typing').text('');
			});

			// --------------- DOM EVENT ------------------------

			$('#open-chat').click(function(){
				socket.emit('openChat', seller, buyer, product);
				$('#chat-wrapper').show();
				$('#open-chat').hide();
				$('#seller-identity > p').text(seller.name);
			});

			$('#send-message').click(function(){
				sendMessage();
			});

			$('#message').keypress(function(e) {
    			if(e.which == 13) {
        			sendMessage();
    			}
			});

			$('#message').keydown(function(e) {
    			socket.emit('typing', seller, buyer, 'buyer');
			});

			$('#message').keyup(function(e) {
				delay(function(){
      				socket.emit('stop-typing', seller, buyer, 'buyer');
    			}, delayTypeTime );
			});

			// ------------------------- FUNCTION ------------------------

			var sendMessage = function(){
				var contentMessage = $('#message').val();
					if(contentMessage != ''){
					var message = constructMessage(contentMessage, buyer, seller);
					
					socket.emit('chatMessage', message);
					message.state = messageState.SENT_BY_CLIENT;
					message.sentByClientDate = new Date().getTime();

					var d = new Date(message.sentByClientDate);
					var hour = d.getHours();
					var minute = d.getMinutes();
					var hm = hour + ':' + minute;

					$('#chat-history > ul').append(
						'<li class="chat-me"><div id="message-' + message.id + '" class="chat-me-div">' +
							contentMessage + 
						'<span class="read-message" id="read-message-' + message.id + '" style="color: green;display: none;font-weight: bold;">R</span>' +
						'<div class="message-time">' + hm + '</div>' +
						'</div><div style="clear:both"></div></li>');

					$('#message').val('');
					socket.emit('stop-typing', seller, buyer, 'buyer');

					var elem = document.getElementById('chat-history');
	  				elem.scrollTop = elem.scrollHeight;

	  				var ll = 8;
	  				var mWidth = ll * message.content.length;
	  				if(mWidth < 50)
	  					mWidth = 50;
	  				else if(mWidth > 180)
	  					mWidth = 180;

	  				$('#message-' + message.id).css('width', mWidth);
  				}
			}
		});
		</script>
		<style>
			
			#chat-history{
				overflow-y: scroll;
			}

			#chat-history ul li .read-message{
				font-family: "arial";
				vertical-align: super;
    			font-size: 8px;
    			margin: 0px 5px 0px 5px;
			}

			#chat-history ul li.chat-me .chat-me-div{
				border: 1px #b3b3ff solid;
    			border-radius: 5px;
    			margin: 5px 10px 0px 0px;
    			font-family: arial;
    			font-size: 13px;
    			text-align:right;
    			padding:5px 5px;
    			float: right;
			}

			#chat-history ul li.chat-me{
				list-style-type: none;
			}

			#chat-history ul li.chat-him{
				list-style-type: none;
				border: 1px #ccc solid;
    			border-radius: 5px;
    			margin: 5px 20px 0px -30px;
    			font-family: arial;
    			font-size: 13px;
    			text-align:left;
    			padding:5px 5px;
			}

			#chat-history ul li .message-time{
    			font-family: arial;
    			font-size: 10px;
    			margin: 4px 0px 0px 0px;
			}

			#chat-wrapper{
				position: absolute; 
				bottom: 0; 
				right: 10;
				margin: 0px;
				padding: 0px;
				display:none;
			}

			#seller-identity{
				border: 1px #ccc solid;
				padding: 0px;
				line-height: 40px;
				margin: 0px;
			}

			#seller-identity p{
				font-size: 13px;
				font-family: arial;
				background-color: #b3b3ff;
				margin: 0px;
				padding: 0px 0px 0px 10px;
			}

			#typing{
				font-weight: bold;
				font-family: arial;
				font-size: 10px;
				color: green;
				margin: -25px 0px 0px 0px;
				padding: 0px 0px 10px 10px;
				height: 20px;
				background-color: #b3b3ff;
			}

			#message{
				padding: 10px 10px;
			}

			#send-message{
				padding: 10px 10px;
			}

			#open-chat{
				display: none
			}
		</style>
	</head>
	<body>
		<input type="button" id="open-chat" value="Chat"/> <span id="seller"></span>	
		<div id="chat-wrapper">
			<div id="seller-identity">
				<p></p>
				<div id="typing"></div>
			</div>
			<div style="height: 300px; width: 250px;border: 1px #ccc solid;" id="chat-history">
				<ul>
				</ul>
			</div>
			<input type="text" id="message"/>&nbsp;<input type="button" id="send-message" value="Send"/>
		</div>
	</body>
</html>