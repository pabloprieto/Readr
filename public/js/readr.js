this.readr = this.readr||{};

(function(){

	var Entry = Backbone.Model.extend({});
	
	var Feed = Backbone.Model.extend({});

	var Feeds = Backbone.Collection.extend({
		model: Feed
	});

	var Entries = Backbone.Collection.extend({
		model: Entry
	});
	
	var ModalView = Backbone.View.extend({
		
		initialize: function()
		{
			this.$el.on('click', $.proxy(this.onClick, this));
		},
		
		show: function()
		{
			this.$el.addClass('in');
			return this;
		},
		
		onClick: function(event)
		{
			if (event.target == this.el || $(event.target).attr('data-toggle') == 'close') {
				event.preventDefault();
				this.close();
			}
		},
		
		close: function()
		{
			this.$el.removeClass('in');
			return this;
		}
		
	});
	
	var AddView = ModalView.extend({
		
		isLoading: false,
		
		events: {
			'submit form': 'onSubmitAdd'
		},
		
		initialize: function()
		{
			this.setElement($('#addModal')[0]);
			ModalView.prototype.initialize.apply(this);
		},
		
		onSubmitAdd: function(event)
		{
			event.preventDefault();
		
			if (this.isLoading) return;
			this.isLoading = true;
		
			var $form = $(event.currentTarget);
			var view	 = this;	
			var feed	 = new Backbone.Model({
				url: $form.find('[name=url]').val(),
				tags: $form.find('[name=tags]').val()
			},{
				urlRoot: $form.attr('action')
			});
			
			$form.find('[type=submit]').attr('disabled', true);
			$form.find('.error').empty();
			
			Backbone.sync('create', feed, {
				complete: function() {
					view.isLoading = false;
					$form.find('[type=submit]').removeAttr('disabled');
				},
				success: function() {
					view.trigger('added', this);
					view.close();
				},
				error: function(data) {
					$form.find('.error').text(data.responseJSON.error);
				}
			});
		}
		
	});
	
	var FeedEditView = ModalView.extend({
		
		template: null,
		
		events: {
			'submit form': 'onSubmit',
			'click [data-toggle=delete]': 'onDelete'
		},
		
		initialize: function()
		{
			this.setElement($('#feedModal')[0]);
			this.template = _.template($('#feedForm').html());
			ModalView.prototype.initialize.apply(this);
		},
		
		render: function()
		{
			this.$el.html(this.template(this.model.attributes));
			return this;
		},
		
		setModel: function(model)
		{
			this.model = model;
			this.render();
			return this;
		},
		
		onSubmit: function(event)
		{
			event.preventDefault();
			
			var tags = _.compact(_.map(this.$('input[name=tags]').val().split(','), function(t){
				 return _.string.trim(t);
			}));
			
			this.model.save({
				title: this.$('input[name=title]').val(),
				url: this.$('input[name=url]').val(),
				tags: tags.join(',')
			}, {patch: true});
			
			this.close();
		},
		
		onDelete: function()
		{
			this.model.destroy();
			this.close();
		}
		
	});
	
	var TagEditView = ModalView.extend({
		
		template: null,
		
		events: {
			'submit form': 'onSubmit',
			'click [data-toggle=delete]': 'onDelete'
		},
		
		initialize: function()
		{
			this.setElement($('#tagModal')[0]);
			this.template = _.template($('#tagForm').html());
			ModalView.prototype.initialize.apply(this);
		},
		
		setTag: function(name)
		{
			this.options.name = name;
			this.render();
			return this;
		},
		
		render: function()
		{
			this.$el.html(this.template({name: this.options.name}));
			return this;
		},
		
		onSubmit: function(event)
		{
			event.preventDefault();
		
			var view	 = this;
			var $form = this.$('form');
		
			$.ajax({
				type: 'PUT',
				data: JSON.stringify({name: $form.find('[name=name]').val()}),
				url: $form.attr('action'),
				complete: function(){
					view.trigger('updated', this);
					view.close();
				}
			});
		},
		
		onDelete: function()
		{
			var view	 = this;
			var $form = this.$('form');
		
			$.ajax({
				type: 'DELETE',
				url: $form.attr('action'),
				complete: function(){
					view.trigger('updated', this);
					view.close();
				}
			});
		}
		
	});

	var TagItemView = Backbone.View.extend({
		
		el: '<div class="item tag-item"></div>',
		template: null,

		events: {
			'click [data-toggle=subnav]': 'onToggleSubnav',
			'click [data-toggle=edit]': 'onEdit'
		},

		initialize: function()
		{
			this.template = _.template($('#tagItem').html());
		},

		render: function()
		{
			this.$el.html(this.template({name: this.options.name}));
			return this;
		},

		onToggleSubnav: function(event)
		{
			event.stopImmediatePropagation();
			this.$el.parent().toggleClass('collapse').find('ul').slideToggle(200);
		},
		
		onEdit: function(event)
		{
			event.stopImmediatePropagation();
			this.trigger('edit', this.options.name);
		}

	});

	var FeedItemView = Backbone.View.extend({
		
		el: '<div class="item feed-item"></div>',
		template: null,
		events: {
			'click [data-toggle=edit]': 'onEdit'
		},

		initialize: function(){
			this.template = _.template($('#feedItem').html());
			this.listenTo(this.model, 'change:title change:unread_count', this.render);
		},

		render: function(){
			this.$el.html(this.template(this.model.attributes));
			return this;
		},

		onEdit: function(event)
		{
			event.stopImmediatePropagation();
			this.trigger('edit', this.model);
		}

	});

	var EntryItemView = Backbone.View.extend({

		tagName: 'li',
		template: null,
		events: {
			'click': 'onClick',
			'click [data-toggle=read]': 'onToggleRead',
			'click [data-toggle=favorite]': 'onToggleFavorite'
		},

		initialize: function(){
			this.template = _.template($('#entryItem').html());
			this.listenTo(this.model, 'change', this.render);
		},

		render: function(){
			this.$el.attr('data-id', this.model.id);
			this.$el.html(this.template(this.model.attributes));
			this.$el.toggleClass('read', this.model.get('read') == 1);
			this.$el.toggleClass('favorite', this.model.get('favorite') == 1);
			return this;
		},

		onClick: function(event)
		{
			this.trigger('select', this);
		},

		onToggleRead: function(event)
		{
			event.stopImmediatePropagation();
			var value = parseInt(this.model.get('read'));
			this.model.save({read: value ? 0 : 1}, {patch: true});
		},
		
		onToggleFavorite: function(event)
		{
			event.stopImmediatePropagation();
			var value = parseInt(this.model.get('favorite'));
			this.model.save({favorite: value ? 0 : 1}, {patch: true});
		}

	});

	var EntryView = Backbone.View.extend({

		tagName: 'li',
		template: null,
		
		events: {
			'click [data-toggle=favorite]': 'onToggleFavorite'
		},

		initialize: function(){
			this.template = _.template($('#entry').html());
		},

		setModel: function(model){
			this.model = model;
			this.render();
		},

		render: function(){
			this.$el.html(this.template(this.model.attributes));
			return this;
		},
		
		onToggleFavorite: function(event)
		{
			event.stopImmediatePropagation();
			var value = parseInt(this.model.get('favorite'));
			this.model.save({favorite: value ? 0 : 1}, {patch: true});
		}

	});

	var ReadrRouter = Backbone.Router.extend({
	
		app: null,
	
		routes: {
			'tag/:name' : 'tag',
			'feed/:id'  : 'feed',
			'entry/:id' : 'entry',
			''          : 'default'
		},
		
		initialize: function(options)
		{
			this.app = options.app;
		},
		
		default: function()
		{
			this.app.setSourceFilter('feed_id', 'all');
			this.app.fetchEntries();
		},
		
		tag: function(name)
		{
			this.app.setSourceFilter('tag', name);
			this.app.fetchEntries();
		},
		
		feed: function(id)
		{
			this.app.setSourceFilter('feed_id', id);
			this.app.fetchEntries();
		},
		
		entry: function(id)
		{
			this.app.fetchEntry(id);
		}
	
	});

	var ReadrApp = Backbone.View.extend({

		router: null,

		feeds: null,
		entries: null,
		
		currentEntry: null,
		
		entryView: null,
		addModal: null,
		feedModal: null,
		tagModal: null,
		
		isLoading: false,
		isAtEnd: false,
		
		params: {
			offset: 0,
			limit: 50
		},

		events: {
			'click [data-toggle=mode]'          : 'onToggleMode',
			'click [data-toggle=filter-status]' : 'onFilterStatus',
			'click [data-toggle=filter-source]' : 'onFilterSource',
			'click [data-toggle=mark-read]'     : 'onMarkAsRead',
			'click [data-toggle=add-feed]'      : 'onAddFeed',
			'click [data-toggle=collapse]'      : 'onToggleCollapse'
		},

		initialize: function()
		{
			this.initFeeds();
			this.initEntries();
			this.initEvents();
			this.fetchFeeds();
			
			this.listenToOnce(this.feeds, 'sync', this.initRouter);
		},
		
		initEvents: function()
		{
			this.$('.entries').on('scroll', $.proxy(this.onScrollEntries, this));
			this.$('.entry').hammer().on('swipeleft swiperight', $.proxy(this.onSwipeEntry, this));
			$(document).on('keypress', $.proxy(this.onKeyPress, this));
		},
		
		initRouter: function()
		{
			this.router = new ReadrRouter({app: this});
			Backbone.history.start();
		},

		initFeeds: function()
		{
			this.feeds = new Feeds([], {
				url: this.options.apiUrl + '/feeds'
			});

			this.listenTo(this.feeds, 'sync', this.onSyncFeeds);
		},

		initEntries: function()
		{
			this.entries = new Entries([], {
				url: this.options.apiUrl + '/entries'
			});

			this.listenTo(this.entries, 'sync', this.onSyncEntries);
			this.listenTo(this.entries, 'reset', this.onResetEntries);
			this.listenTo(this.entries, 'add', this.onAddEntry);
			this.listenTo(this.entries, 'change:read', this.onChangeRead);
		},

		fetchFeeds: function()
		{
			this.feeds.fetch({reset: true});
		},

		fetchEntries: function(reset)
		{
			this.setMode('entries');
			this.updateTitle();
			this.updateActiveSourceItem();
			this.toggleCollapse('#options', false);
		
			if (reset === undefined) reset = true;
			
			if (reset) {
				this.$('.entries-list').empty();
				this.isAtEnd = false;
				this.params.offset = 0;
			} else if(this.isLoading || this.isAtEnd) {
				return;
			}
			
			this.$('.entries .loading').show();
			
			this.isLoading = true;
			this.entries.fetch({reset: reset, remove: reset, data: this.params});
			this.params.offset += this.params.limit;
		},
		
		updateTitle: function()
		{
			var title = 'All items';
		
			if (this.params.tag) {
				title = this.params.tag;
			} else if (this.feeds.length && this.params.feed_id && this.params.feed_id != 'all') {
				title = this.feeds.get(this.params.feed_id).get('title');
			}
			
			this.$('.app-header .feed-title').text(title);
		},

		setStatusFilter: function(name, value)
		{
			delete this.params.read;
			delete this.params.favorite;
			if(value != 'all') this.params[name] = value;

			this.$('[data-toggle=filter-status]').each(function(i, e) {
				var $e = $(e).removeClass('active');
				if (
					(value == 'all' && $e.attr('value') == value) ||
					($e.attr('name') == name && $e.attr('value') == value)
				) {
					$e.addClass('active');
				}
			});
		},

		setSourceFilter: function(name, value)
		{
			delete this.params.feed_id;
			delete this.params.tag;
			if(value != 'all') this.params[name] = value;
		},
		
		updateActiveSourceItem: function()
		{
			var name  = 'feed_id';
			var value = 'all';
			
			if (this.params.tag) {
				name = 'tag';
				value = this.params.tag;
			} else if (this.params.feed_id) {
				value = this.params.feed_id;
			}
		
			this.$('[data-toggle=filter-source]').each(function(i, e) {
				var $e = $(e);
				$e.parent().removeClass('active');
				if (
					(value == 'all' && $e.attr('value') == value) ||
					($e.attr('name') == name && $e.attr('value') == value)
				) {
					$e.parent().addClass('active');
				}
			});
		},
		
		toggleCollapse: function(selector, switcher)
		{
			this.$(selector).toggleClass('in', switcher);
		},
		
		fetchEntry: function(id)
		{
			var entry = this.entries.get(id);
		
			if (!entry) {
				// Direct access, create a new entry and fetch the collection
				entry = new Entry({id:id}, {
					urlRoot: this.entries.url
				});
				this.entries.fetch();
			}
			
			if (entry.get('content') == undefined) {
				entry.once('change', this.displayEntry, this);
				entry.fetch();
			} else {
				this.displayEntry(entry);
			}
		},

		displayEntry: function(entry)
		{
			this.setMode('entry');
		
			this.$('.entries-list > .active').removeClass('active');
			this.$('.entries-list > [data-id=' + entry.id + ']').addClass('active');

			if (entry.get('read') == 0) {
				entry.save({read:1}, {patch: true});
			}

			if (!this.entryView) {
				this.entryView = new EntryView({
					el: this.$('.app-body .entry')[0]
				});
			}

			this.entryView.$el.scrollTop(0);
			this.entryView.setModel(entry);
			
			this.currentEntry = entry;
		},

		addEntryItem: function(entry)
		{
			var view = new EntryItemView({
				model: entry
			}).render();
			
			view.$el.toggleClass('active', this.currentEntry != null && this.currentEntry.id == entry.id);

			this.listenTo(view, 'select', this.onSelectEntry);
			this.$('.entries-list').append(view.el);
		},
		
		buildFeedsMenu: function()
		{
			var app = this;
			var $container = this.$('.feeds-list').empty();
			var tags = new Array;
			var unclassified = new Array;
			
			this.feeds.each(function(feed) {
				if (!feed.get('tags')) {
					unclassified.push(feed);
					return;
				}
				var t = feed.get('tags').split(',');
				for (var i = 0, l = t.length; i < l; i++) {
					if (_.indexOf(tags, t[i]) == -1) tags.push(t[i]);
				}
			});

			tags.sort();

			var i, l, view;

			for (i = 0, l = tags.length; i < l; i++) {
				var tag = tags[i];
				view = new TagItemView({name: tag}).render();
				this.listenTo(view, 'edit', this.onEditTag);
				
				var $item = $('<li/>');
				var $feedsContainer = $('<ul></ul>');

				$item.append(view.el);

				this.feeds.each(function(feed) {
					if (feed.get('tags') == null) {
						return;
					}
					var t = feed.get('tags').split(',');
					if (_.indexOf(t, tag) > -1) {
						
						var view = new FeedItemView({model: feed}).render();
						app.listenTo(view, 'edit', app.onEditFeed);
						
						var $item = $('<li/>');
						$item.append(view.el);
						
						$feedsContainer.append($item);
						
					}
				});

				$item.append($feedsContainer);
				$container.append($item);
			}
			
			for (i = 0, l = unclassified.length; i < l; i++) {
				view = new FeedItemView({model: unclassified[i]}).render();
				this.listenTo(view, 'edit', this.onEditFeed);
				$item = $('<li/>');
				$item.append(view.el);
				$container.append($item);
			}
			
			this.updateActiveSourceItem();
		},
		
		setMode: function(mode)
		{
			if (!mode) mode = 'entries';
			this.$('.app-body').attr('data-mode', mode);
		},

		onSyncFeeds: function()
		{
			this.buildFeedsMenu();
			this.updateTitle();
			this.listenTo(this.feeds, 'change:tags', this.buildFeedsMenu);
			this.listenTo(this.feeds, 'remove', this.buildFeedsMenu);
		},

		onMarkAsRead: function(event)
		{
			event.preventDefault();
			
			this.toggleCollapse('#options', false);

			var data = _.clone(this.params);
			data.read = 1;

			Backbone.sync('update', this.entries, {data: JSON.stringify(data)});

			this.entries.each(function(entry){
				entry.set({read: 1});
			});

			if (this.params.feed_id) {
				
				this.feeds.get(this.params.feed_id).set('unread_count', 0);
			
			} else if (this.params.tag) {
				
				var tag = this.params.tag;
				var feeds = new Array;
				this.feeds.each(function(feed) {
					var tags = feed.get('tags').split(',');
					if (_.indexOf(tags, tag) > -1) feeds.push(feed);
				});

				_.each(feeds, function(feed) {
					feed.set('unread_count', 0);
				});

			} else {

				this.feeds.each(function(feed) {
					feed.set('unread_count', 0);
				});

			}
		},
		
		onAddFeed: function()
		{
			if (!this.addModal) {
				this.addModal = new AddView();
				this.listenTo(this.addModal, 'added', this.fetchFeeds);
			}
			
			this.addModal.show();
			this.toggleCollapse('#options', false);
		},
		
		onEditFeed: function(feed)
		{
			if (!this.feedModal) {
				this.feedModal = new FeedEditView();
			}
			
			this.feedModal.setModel(feed).show();
		},
		
		onEditTag: function(name)
		{
			if (!this.tagModal) {
				this.tagModal = new TagEditView();
				this.listenTo(this.tagModal, 'updated', this.fetchFeeds);
				this.listenTo(this.tagModal, 'deleted', this.fetchFeeds);
			}
			
			this.tagModal.setTag(name).show();
		},
		
		onSyncEntries: function(model, resp)
		{
			if (model == this.entries && resp.length < this.params.limit) {
				this.isAtEnd = true;
			}
			
			this.isLoading = false;
			this.$('.entries .loading').hide();
		},

		onResetEntries: function()
		{
			var app = this;

			this.entries.each(function(entry){
				app.addEntryItem(entry);
			});
		},

		onAddEntry: function(model)
		{
			this.addEntryItem(model);
		},

		onFilterStatus: function(event)
		{
			event.preventDefault();

			var $btn  = $(event.currentTarget);
			var name  = $btn.attr('name');
			var value = $btn.attr('value');

			this.setStatusFilter(name, value);
			this.fetchEntries();
		},

		onFilterSource: function(event)
		{
			event.preventDefault();

			var $btn  = $(event.currentTarget);
			var name  = $btn.attr('name');
			var value = $btn.attr('value');
			
			switch (name) {
				case 'tag':
					this.router.navigate('tag/' + value, {trigger: true});
					break;
				case 'feed_id':
					this.router.navigate('feed/' + value, {trigger: true});
					break;
			}
		},

		onSelectEntry: function(item)
		{
			if (item.$el.hasClass('active')) {
				this.setMode('entry');
			} else {
				this.router.navigate('entry/' + item.model.id, {trigger: true});
			}
		},
		
		onChangeRead: function(entry, value)
		{
			var feed = this.feeds.get(entry.get('feed_id'));
			var count = parseInt(feed.get('unread_count'));
			feed.set({unread_count: value ? Math.max(count - 1, 0) : count + 1});
		},
		
		onScrollEntries: function(event)
		{
			var $entries = this.$('.entries');
			var h = $entries[0].scrollHeight - $entries.height();
			var s = $entries.scrollTop();
			if (s > 0 && h == s) this.fetchEntries(false);
		},
		
		onKeyPress: function(event)
		{
			// Ignore if:
			// - No entry selected
			// - The command key is pressed
			// - Focus is on an input field
			if (!this.currentEntry || event.metaKey || $(event.target).is(':input')) return;
			
			var code = event.which || event.keyCode;
		
			event.preventDefault();
		
			switch (code) {
				case 32: // space
					var index = this.entries.indexOf(this.currentEntry);
					var entry = this.entries.at(index + (event.shiftKey ? -1 : 1));
					if (entry) this.router.navigate('entry/' + entry.id, {trigger: true});
					break;
					
				case 109: // m/r: toggle read
				case 114: 
					var read = parseInt(this.currentEntry.get('read'));
					this.currentEntry.save({read: read == 0 ? 1 : 0}, {patch: true});
					break;
					
				case 102: // f/s: toggle favorite
				case 115:
					var favorite = parseInt(this.currentEntry.get('favorite'));
					this.currentEntry.save({favorite: favorite == 0 ? 1 : 0}, {patch: true});
					break; 
				
				case 118: // v: view
					window.open(this.currentEntry.get('link'));
					break;
			}
		},
		
		onSwipeEntry: function(event)
		{
			var direction = event.gesture.direction == 'left' ? 1 : -1;
			
			var index = this.entries.indexOf(this.currentEntry);
			var entry = this.entries.at(index + direction);
			if (entry) this.router.navigate('entry/' + entry.id, {trigger: true});
		},
		
		onToggleMode: function(event)
		{
			var mode  = this.$('.app-body').attr('data-mode');
			var value = $(event.currentTarget).attr('value');
			this.setMode(mode != value ? value : false);
		},
		
		onToggleCollapse: function(event)
		{
			var selector = $(event.currentTarget).attr('data-target');
			this.toggleCollapse(selector);
		}

	});

	readr.ReadrApp = ReadrApp;

}());