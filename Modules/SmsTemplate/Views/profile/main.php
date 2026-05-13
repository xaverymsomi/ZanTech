<div class="panel panel-default" style="background-color: whitesmoke">
    <div class="panel-body">
        <h5 class="text-center" style="font-size: 2.0em; color: white; font-weight: 600; margin-top: 10px; padding: 10px; border-radius: 4px; background-color: {{ initial_tab_data['Color']}}">
            {{ initial_tab_data['Status'] }}
        </h5>
        <div class="row" style="margin-top: 20px;">
            <div class="col-md-12 col-lg-12">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <h5 class="text-center" style="font-size: 1.3em; text-transform: uppercase">SMS Template Details</h5>
                        <hr/>
                        <div class="row">
                            <div class="col-md-12 col-lg-12">
                                <div class="row">
                                    <div class="col-md-12 col-lg-12">
                                        <div class="panel panel-default">
                                            <div class="panel-body">
                                                <div class="row">
                                                    <div class="col-md-6 col-lg-6">
                                                        <h5 style="font-size: 1.35em; float: left;">Sender</h5>
                                                    </div>
                                                    <div class="col-md-6 col-lg-6">
                                                        <h5 style="font-size: 1.4em; color: green; font-weight: 600; float: right;">{{ initial_tab_data['Sender'] }}</h5>
                                                    </div>
                                                </div>
                                                <hr style="margin: 0; opacity: 0.6;"/>
                                                <div class="row">
                                                    <div class="col-md-6 col-lg-6">
                                                        <h5 style="font-size: 1.35em; float: left;">Source</h5>
                                                    </div>
                                                    <div class="col-md-6 col-lg-6">
                                                        <h5 style="font-size: 1.4em; color: orange; font-weight: 600; float: right;">{{ initial_tab_data['Source'] }}</h5>
                                                    </div>
                                                </div>

                                                <hr style="margin: 0; opacity: 0.6;"/>
                                                <div class="row">
                                                    <div class="col-md-6 col-lg-6">
                                                        <h5 style="font-size: 1.35em; float: left;">SMS Language</h5>
                                                    </div>
                                                    <div class="col-md-6 col-lg-6">
                                                        <h5 style="font-size: 1.4em; font-weight: 600; float: right;">{{ initial_tab_data['SMS Language'] }}</h5>
                                                    </div>
                                                </div>
                                                <hr style="margin: 0; opacity: 0.6;"/>
                                                <div class="row">
                                                    <div class="col-md-6 col-lg-6">
                                                        <h5 style="font-size: 1.35em; float: left;">Content</h5>
                                                    </div>
                                                    <div class="col-md-6 col-lg-6">
                                                        <h5 style="font-size: 1.4em; font-weight: 600; float: right;">{{ initial_tab_data['Content'] }}</h5>
                                                    </div>
                                                </div>
                                                <hr style="margin: 0; opacity: 0.6;"/>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
