var donut = {
	tooltip: {
		headerFormat: '<b>{point.key}</b><br>',
		pointFormat: '<span style="color:{point.color}">● </span> {point.y} ({point.percentage:.0f}%)',
	},
	plotOptions: {
		pie: {
			allowPointSelect: true,
			cursor: 'pointer',
			dataLabels: {
				enabled: false
			},
			innerSize: '55%',
		}
	}
};

var BarChartStacked = {
		chart: {
			type: 'column',
		},
		tooltip: {
			useHTML: true,
			headerFormat: '<b>{point.key}</b><br>',
			pointFormat: '<span style="color:{series.color}">● </span> {series.name} ${point.y} MM',
		},
		yAxis: {
			min: 0,
			reversedStacks: true,
			title: { text: '' },
			labels: {
				enabled: false
   			},
   			gridLineWidth: 0,
            minorGridLineWidth: 0,
		},
		xAxis: {
			lineWidth: 0,
			minorGridLineWidth: 0,
			lineColor: 'transparent',
			minorTickLength: 0,
			tickLength: 0
		},
		legend: {
            align: 'right',
            verticalAlign: 'middle',
            layout: 'vertical',
        },
		plotOptions: {
			series: {
				stacking: 'normal',
			}
		},
	}
	
	
	var BarChartStacked2 = {
		chart: {
			type: 'column',
		},
		tooltip: {
			useHTML: true,
			headerFormat: '<b>{point.key}</b><br>',
			pointFormat: '<span style="color:{series.color}">● </span> {series.name} ${point.y} MM',
		},
		yAxis: {
			min: 0,
			reversedStacks: true,
			title: { text: 'The metric' },
		},
		xAxis: {
		},
		legend: {
            align: 'right',
            verticalAlign: 'middle',
            layout: 'vertical',
        },
		plotOptions: {
			series: {
				stacking: 'normal',
			}
		},
	}
	
	
	var BarChartPipeline = {
		chart: {
			type: 'bar',
      spacing: 0
		},
		tooltip: {
			enabled: false
		},
		yAxis: {
			min: 0,
			reversedStacks: false,
			title: { text: '' },
			labels: {
				enabled: false
   			},
   			gridLineWidth: 0,
            minorGridLineWidth: 0,
		},
		xAxis: {
			lineWidth: 0,
			minorGridLineWidth: 0,
			lineColor: 'transparent',
			minorTickLength: 0,
			tickLength: 0,
			labels: {
				enabled: false
   	  }
		},
		legend: {
      align: 'center',
      verticalAlign: 'bottom',
      layout: 'horizontal',
    },
		plotOptions: {
			series: {
				stacking: 'percent',
				pointWidth: 35,
				dataLabels: {
            enabled: true,
            color: '#fff',
            style: {textShadow: 'none',fontSize: '14px'},
            formatter: function() {return this.y},
            inside: true,
            shadow: false
        },
			}
		},
	}
	
var mediumPie = {
	tooltip: {
		headerFormat: '<b>{point.key}</b><br>',
		pointFormat: '<span style="color:{point.color}">● </span> ${point.y}M ({point.percentage:.0f}%)',
	},
	plotOptions: {
		pie: {
			allowPointSelect: true,
			cursor: 'pointer',
			dataLabels: {
				enabled: false
			},
			showInLegend: true,
			innerSize: '50%',
		}
	},
	legend: {
		layout: 'vertical',
		align: 'right',
		verticalAlign: 'middle',
		useHTML: true,
		itemStyle: { fontSize: '12px'},
    labelFormatter: function() {
			return ('<span style="width:150px; height: 16px; overflow:hidden; font-size: 12px; line-height: 16px; text-overflow:ellipsis; display:block; white-space:nowrap;" title="' + this.name + '">' + this.name + '</span>');
		}
	}
}

var lineChart = {
  

        yAxis: {
            title: {
                text: 'Sales ($M)'
            },
            min : 0,
            plotLines: [{
                value: 0,
                width: 1,
                color: '#808080'
            }]
        },

}



	var BarChartStacked2 = {
		chart: {
			type: 'column',
		},
		tooltip: {
			useHTML: true,
			headerFormat: '<b>{point.key}</b><br>',
			pointFormat: '<span style="color:{series.color}">● </span> {series.name} {point.y}%',
		},
		yAxis: {
			min: 0,
			reversedStacks: false
		},
		plotOptions: {
			series: {
				stacking: 'normal'
			}
		},
	}
	// Column Chart
	var ColumnChartStacked = {
		chart: {
			type: 'column',
		},
		tooltip: {
			headerFormat: '<b>{point.key}</b><br>',
			pointFormat: '<span style="color:{series.color}">● </span> {series.name} {point.y}%',
		},
		plotOptions: {
			column: {
				stacking: 'normal'
			}
		},
		yAxis: {
			min: 0,
			reversedStacks: false

		},
	}