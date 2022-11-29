/**
 * Sand-Signika theme for Highcharts JS
 * @author Torstein Honsi
 */




Highcharts.theme = {
   colors: ["#418ac9", "#f1af42", "#96c93d", "#f47c6c", "#58c0e0", "#f36d24",
   			"#275379", "#916928", "#5a7925", "#924a41", "#357386", "#924116",
   			"#8db9df", "#f7cf8e", "#c0df8b", "#f8b0a7", "#9bd9ec", "#f8a77c",
           ],
   chart: {
      backgroundColor: null,
      style: {
         fontFamily: "Helvetica, Arial, serif"
      },
	  spacing: 5
   },
   lang: {
        thousandsSep: ','
    },
   title: {
			text: ''
   },
   credits: {
      enabled: false
   },
   tooltip: {
      borderWidth: 0,
      backgroundColor: 'rgba(68,68,68,0.9)',
      shadow: false,
	  borderRadius: 3,
	  style: {
		color: '#ffffff',
		fontSize: '12px',
		padding: '10px'
	  }
   },
   legend: {
      itemStyle: {
         fontWeight: 'normal',
         fontSize: '12px'
      }
   },
   xAxis: {
	  lineColor: '#e5e5e5',
      lineWidth: 1,
      tickWidth: 1,
      tickColor: '#e5e5e5',
      labels: {
         style: {
			fontSize: '11px',
            color: '#444'
         }
      },
	  title: {
         style: {
            color: '#444',
            fontWeight: 'bold',
            fontSize: '12px'
		}
      }
   },
   yAxis: {
	  gridLineWidth: 1,
	  lineColor: '#e5e5e5',
	  gridLineColor: '#f5f5f5',
      labels: {
         style: {
			fontSize: '11px',
            color: '#444'
         }
      },
	  title: {
         style: {
            color: '#444',
            fontWeight: 'bold',
            fontSize: '12px'
		}
      }
   },
   plotOptions: {
      series: {
         shadow: false
      },
      candlestick: {
         lineColor: '#e5e5e5'
      }
   }
   
};

// Apply the theme
Highcharts.setOptions(Highcharts.theme);