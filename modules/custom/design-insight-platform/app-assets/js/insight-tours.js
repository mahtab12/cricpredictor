var startTemplate = "<div class='popover tour start-template'><div class='arrow'></div><h3 class='popover-title text-center'></h3><div class='popover-content text-center'></div><div class='popover-navigation start-tour'><button class='btn btn-primary' data-role='next'>Start tour</button><button class='btn btn-default' data-role='end'>No thanks</button></div></div>";
var standardTemplate = "<div class='popover tour'><div class='arrow'></div><h3 class='popover-title'></h3><div class='popover-content'></div><div class='popover-navigation'><button class='btn btn-default' data-role='prev'>« Prev</button><button class='btn btn-default' data-role='next'>Next »</button><button class='btn btn-default' data-role='end'>End this tour</button></div>";

// Home Page Tour
var homeTour = new Tour({
  template: standardTemplate,
  steps: [{
    element: "#tourHomeWelecome",
    title: "Welcome to Insight Platform",
    content: "This is the first time you've landed on the home page. Let us guide you through the key features.",
    template: startTemplate,
    placement: "top",
  },{
    element: "#search-box",
    content: "Quickly find what you're you're looking for with our intuitive search. As you start typing we'll suggest topics you might be interested in.",
    placement: "top",
  },{
    element: ".home-products-container",
    content: "If you prefer to browse, simply choose a starting point below. Hover over each item for more information.",
    placement: "top",
  },{
    element: "#tourNavigation",
    content: "Wherever you are in the site you can browse by Disease, Drug and Company using the top navigation. ",
    placement: "left",
  },{
    element: "#tourHomeMore",
    content: "Other tools and dashboards are available from the 'More' menu.",
    placement: "left",
  }]
});

// Disease/Epi Browse Page Tour
var diseaseBrowseTour = new Tour({
  template: standardTemplate,
  steps: [{
    element: "#tourBrowseIntroDisease",
    title: "Welcome to Disease Insights",
    content: "This is the first time you've landed here. Let us guide you through the key features.",
    template: startTemplate,
    placement: "right",
  },{
    element: "#tourBrowseIntroEpi",
    title: "Welcome to Epidemiology",
    content: "This is the first time you've landed here. Let us guide you through the key features.",
    template: startTemplate,
    placement: "right",
  },{
    element: "#tourBrowseSearch",
    content: "Quickly filter the diseases by typing into the search box. Try it for yourself.",
    placement: "top",
  },{
    element: "#tourBrowseTAFilter",
    content: "Filter by therapy area by opening this dropdown and checking your areas of interest.",
    placement: "top",
  },{
    element: "#tourBrowseOwnedToggle",
    content: "Toggle between diseases in your subscription. Green ticks indicate those that in your subscription, while the red locks indicate those that are not.",
    placement: "top",
  },{
    element: "#content-container",
    content: "Once you've found what you're looking for, simply click on the disease you're interested in.",
    placement: "top",
  }]
});

// Drug/Company Browse Page Tour
var drugBrowsetour = new Tour({
  template: standardTemplate,
  steps: [{
    element: "#tourBrowseIntroDrug",
    title: "Drug Insights",
    content: "This is the first time you've landed here. Let us guide you through the key features.",
    template: startTemplate,
    placement: "right",
  },{
    element: "#tourBrowseIntroCompany",
    title: "Welcome to Company Insights",
    content: "This is the first time you've landed here. Let us guide you through the key features.",
    template: startTemplate,
    placement: "right",
  },{
    element: "#tourBrowseSearch",
    content: "Quickly filter the page by typing into the search box. Try it for yourself.",
    placement: "top",
  },{
    element: "#content-container",
    content: "Once you've found what you're looking for, simply click on the item you're interested in.",
    placement: "top",
  }
]});

// Content Page Tour
var contentTour = new Tour({
  template: standardTemplate,
  steps: [{
    title: "Welcome to a Content Page",
    content: "This is the first time you have landed on a page like this. Let us guide you through the key features.",
    orphan: true,
    template: startTemplate
  },{
    element: "#downloads",
    content: "You can download the Landscape & Forecast (formerly Pharmacor) with this button.",
    placement: "right",
  },{
    element: "#tourContentNavigation",
    content: "Navigate through the key sections using the tabs.",
    placement: "bottom",
  },{
    element: "#tourDiseaseToc",
    content: "This column allows you to navigate through this section via the expandable table of contents.",
    placement: "top",
  },{
    element: "#tourDiseaseContent",
    content: "This column contains all our analysis, figures, tables and data.",
    placement: "top",
  },{
    element: "#tourDiseaseRelated",
    content: "This column contains links to downloads, our Ask the Expert feature and related research.",
    placement: "top",
  }]
});

// Epi Data Tour
var epiTour = new Tour({
  template: standardTemplate,
  steps: [{
    title: "Welcome to Epidemiology Data",
    content: "This is the first time you have landed on a page like this. Let us guide you through the key features.",
    orphan: true,
    template: startTemplate,
  },{
    element: "#tourEpiPopulations",
    content: "You can toggle between different populations using this dropdown menu.",
    placement: "top",
  },{
    element: "#tourEpiAnalyst",
    content: "Got a question for the Epidemiologist? Simply click here and ask your question.",
    placement: "top",
  },{
    element: "#tourEpiDownloads",
    content: "You can download data across all populations using this dropdown menu.",
    placement: "top",
  },{
    element: "#tourEpiTabs",
    content: "Use these tabs to view different segments of the data.",
    placement: "left",
  },{
    element: "#tourEpiFilters",
    content: "You can change what data is displayed using these filters.",
    placement: "left",
  },{
    element: "#tourEpiToggle",
    content: "You can use this to toggle what metric is shown.",
    placement: "top",
  }]
});

// Search Tour
var searchTour = new Tour({
  template: standardTemplate,
  steps: [{
    title: "Welcome to your Search Result",
    content: "This is the first time you have landed on a search page. Let us guide you through the key features.",
    orphan: true,
    template: startTemplate,
  },{
    element: "#tourSearchIntro",
    content: "You can amend your search term(s) in here.",
    placement: "right",
  },{
    element: "#tourSearchTabs",
    content: "These tabs allows you to switch between all search results, figures, tables and reports.",
    placement: "bottom",
  },{
    element: "#tourSearchReports",
    content: "Click into here to download older reports in your subscription.",
    placement: "bottom",
  },{
    element: "#tourSearchOwnedToggle",
    content: "Keep this box checked if you just want to view results from your subscription.",
    placement: "bottom",
  },{
    element: "#searchResults",
    content: "Your search results are listed here. Simply click the one you're interested in.",
    placement: "top",
  }]
});
