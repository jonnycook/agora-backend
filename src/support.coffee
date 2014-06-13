$ ->
	time = null
	chatsEl = $('<div id="chats" />').appendTo 'body'

	users = {}
	supportStaff = {}

	chats = {}

	messageQueue = []

	sendMessage = (message, userId) ->
		messageQueue.push message:message, userId:userId

	userName = (id) ->
		if users[id].name
			users[id].name
		else
			users[id].email

	supportName = (id) ->
		supportStaff[id]?.name ? 'Support'

	createChatEl = (userId) ->
		chatEl = $("
			<div class='chat'>
				<span class='user'>#{userName userId}</span>
				<div class='wrapper'>
					<div class='messages' />
				</div>
				<input type='text' class='sendMessage'>
			</div>").appendTo chatsEl
		chatEl.find('.sendMessage').keyup (e) ->
			if e.keyCode == 13
				sendMessage @value, userId
				@value = ''
		chatEl

	createMessageEl = (message) ->
		sender = null
		if message.sender
			sender = supportName message.sender
		else
			sender = userName message.user_id
		$("<div class='message'>
				<span class='sender'>#{sender}</span><span class='content'>#{message.content}</span>
			</div>")

	update = ->
		$.post 'http://ext.agora.sh/supportUpdate.php?id=' + supportStaffId, time:time ? 0, messages:messageQueue, ((response) ->
			time = response.time

			for user in response.users
				users[user.id] = user

			for supportStaffMember in response.supportStaff
				supportStaff[supportStaffMember.id] = supportStaffMember

			for message in response.messages
				if !chats[message.user_id]
					chatEl = createChatEl message.user_id
					chatEl.css left:(chatsEl.find('.chat').length - 1)*chatEl.width()
					chats[message.user_id] = el:chatEl
				chats[message.user_id].el.find('.messages').append createMessageEl message

			setTimeout (->update()), 1000
		), 'json'
		messageQueue = []
	update()