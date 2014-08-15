var app = app || {};

app.BusinessView = Backbone.View.extend({
    tagName: 'div',
    className: 'row business',
    template: _.template( $( '#business' ).html() ),
    templateFull: _.template( $( '#fullBusiness' ).html() ),

    events: {
    	"click": "fullBusiness"
    },

    render: function() {
        this.$el.html( this.template( this.model.attributes ) );

        return this;
    },

    fullBusiness: function(e) {
    	location.href = "#business/"+ this.model.get('id');
    },

    renderFull: function() {
        //this.model.beers = new app.Beer();
        //this.model.beers.fetch({data: {businessId: this.model.id}});
    	app.AppView.BusinessesView.$el.html( this.templateFull( this.model.attributes ) );
    }
});