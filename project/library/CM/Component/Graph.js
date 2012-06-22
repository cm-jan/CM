series: null,
flotOptions: null,

ready: function() {
	this.updateGraph(this.series);
},

updateGraph: function(series) {
	var flotSeries = [];
	_.each(series, function(serie) {
		var flotSerie = {};
		flotSerie.label = serie.label;
		flotSerie.data = [];
		_.each(serie.data, function(value ,key) {
			var flotKey = key;
			if ('time' == this.flotOptions.xaxis.mode) {
				flotKey = key + '000';
			}
			flotSerie.data.push([flotKey, value]);
		}, this);
		flotSeries.push(flotSerie);
	}, this);
	$.plot(this.$(), flotSeries, this.flotOptions);
}