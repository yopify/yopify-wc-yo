(function( $ )
{
    'use strict';

    var syncLogsHolder = $( '#yopify-yo-sync-logs' );
    var $yopifyYoAllowAccessHolder = $( '#yopifyYoAllowAccessHolder' );
    var $yopifyYoAccessButton = $( '#yopifyYoAllowAccessButton' );
    var $yopifyYoProgressBar = $( '.progress-bar-holder .progress' );

    if( typeof yopifyYoCheckLoginUrl != "undefined" )
    {
        $.getJSON( yopifyYoCheckLoginUrl, function( response )
        {
            if( response.status == '1' )
            {
                var myApps = response.apps;

                if( myApps )
                {
                    $yopifyYoAllowAccessHolder.find( 'h3' ).text( "Verification required" );

                    var user = response.user;
                    var selectAppOptionsHtml = '';

                    if( myApps.length > 1 )
                    {
                        $yopifyYoAllowAccessHolder.find( 'h3' ).text( "Verification required" );

                        $yopifyYoAccessButton.text( "Accept" ).attr( 'href', yopifyYoCreateTokensUrl ).data( 'select-app', 0 );
                        $( '#yopifyYoAllowAccessHolder' ).show();

                        $.each( myApps, function( index, app )
                        {
                            selectAppOptionsHtml += '<option value="' + app.app_id + '" data-clientid="' + app.client_id + '">' + app.app_id + ' | ' + app.title + ' | ' + app.url + '</option>';
                        } );

                    }
                    else
                    {
                        var app = myApps[0];
                        selectAppOptionsHtml += '<option value="' + app.app_id + '" selected data-clientid="' + app.client_id + '">' + app.app_id + ' | ' + app.title + ' | ' + app.url + '</option>';
                    }

                    $yopifyYoAccessButton.text( "Accept" ).attr( 'href', 'javascript:void(0);' ).data( 'select-app', 1 );
                    $yopifyYoAllowAccessHolder.find( 'p' ).html( "<div class='form-label-area'>Name:</div> <div class='form-value-area'> " + user.name + "</div> <div class='form-label-area'> Email: </div>  <div class='form-value-area email-overlow'>" + user.email + " </div> <div class='form-label-area'>App:</div> <div class='form-value-area'> <div class='custom-select'><select id='yopify_yo_selected_app'><option value=''>Select your app</option>" + selectAppOptionsHtml + "</select></div></div><br /><br /><div class='form-text'>Please select the correct app and click Accept if this is the correct account.</div>" );
                    $( '#switchAccount' ).show();
                    $yopifyYoAllowAccessHolder.show();
                }

                $( '.yo_loading' ).hide();
            }
            else
            {
                $( '.yo_loading' ).hide();
                $yopifyYoAllowAccessHolder.show();
                $yopifyYoAccessButton.attr( 'href', yopifyYoLoginUrl ).attr( 'target', '_blank' ).data( 'select-app', 0 );
            }
        } );

    }

    $( document ).on( 'click', '#yopifyYoAllowAccessButton', function()
    {
        if( $( this ).data( 'select-app' ) == '1' )
        {
            if( ! $( '#yopify_yo_selected_app' ).val() )
            {
                alert( "Please select app." );
                return false;
            }

            $( '.yo_loading' ).show();

            $.post( yopifyYoSetCurrentAppUrl, {
                'app_id': $( '#yopify_yo_selected_app' ).val(),
                'client_id': $( '#yopify_yo_selected_app option:selected' ).data( 'clientid' )
            }, function( response )
            {
                response = $.parseJSON( JSON.stringify( response ) );

                if( response && response.success )
                {
                    $.getJSON( yopifyYoGetAccessTokenUrl, function( response )
                    {
                        if( response.token )
                        {
                            $.post( yopifyYoSetAccessTokenUrl, {'token': response.token}, function( response )
                            {
                                response = $.parseJSON( JSON.stringify( response ) );

                                if( response && response.success )
                                {
                                    if( confirm( "Success: Do you want to sync your store's orders with Yo now?" ) )
                                    {
                                        window.location.href = yopifyYoSyncOrdersUrl;
                                    }
                                    else
                                    {
                                        window.location.reload();
                                    }
                                }
                                else
                                {
                                    $( '.yo_loading' ).hide();
                                    alert( "An unknown error has occurred while setting up token" );
                                }
                            }, 'json' )
                        }
                        else if( response.code == 401 )
                        {
                            $( '.yo_loading' ).hide();
                            $yopifyYoAllowAccessHolder.show();
                            $yopifyYoAccessButton.attr( 'href', yopifyYoLoginUrl ).attr( 'target', '_blank' ).data( 'select-app', 0 );
                        }
                        else if( response.code == 404 )
                        {
                            $( '.yo_loading' ).hide();
                            $yopifyYoAllowAccessHolder.find( 'h3' ).text( "A token is required" );
                            $yopifyYoAllowAccessHolder.find( 'p' ).text( "Clicking 'Create Token' button will redirect you to a page where you can create a token. Once token is created, Please refresh this window." );
                            $yopifyYoAccessButton.text( "Create Token" ).attr( 'href', yopifyYoCreateTokensUrl ).attr( 'target', '_blank' ).data( 'select-app', 0 );
                            $yopifyYoAllowAccessHolder.show();
                        }
                        else
                        {
                            alert( "An unknown error has occurred" );
                        }
                    } );
                }
                else
                {
                    $( '.yo_loading' ).hide();
                    alert( "An unknown error has occurred while setting up app" );
                }
            }, 'json' )
        }
    } );

    $( document ).on( 'click', '#switchAccount', function()
    {
        window.location.reload();
    } );

    function yopifyYoSyncOrders( pageNo, totalOrders )
    {
        if( totalOrders > 0 )
        {
            if( totalOrders >= pageNo )
            {
                $.getJSON( ajaxurl + '?action=yopify_yo_sync_orders&page=' + pageNo, function( response )
                {
                    if( response.status == '1' )
                    {
                        var step = Math.ceil( 100 / totalOrders );
                        $yopifyYoProgressBar.css( {
                            width: (step * pageNo) + '%'
                        } );

                        if( totalOrders > pageNo )
                        {
                            var nextPage = pageNo + 1;
                            yopifyYoSyncOrders( nextPage, totalOrders );
                        }
                        else
                        {
                            syncLogsHolder.prepend( '<li>Sync completed.</li>' );
                            syncLogsHolder.prepend( '<li><a href="' + yopifyYoDashboardUrl + '">Click here</a> to go to Yo dashboard.</li>' );
                        }
                    }
                    else
                    {
                        syncLogsHolder.prepend( '<li>' + pageNo + ' order(s) were synced.</li>' );
                        syncLogsHolder.prepend( '<li class="error">' + (typeof response.error != "undefined" ? response.error : "Unknown error") + ' Please try after 1 hour.</li>' );
                        syncLogsHolder.prepend( '<li><a href="' + yopifyYoDashboardUrl + '">Click here</a> to go to Yo dashboard.</li>' );
                    }
                } );
            }
            else
            {
                syncLogsHolder.prepend( '<li>Sync completed.</li>' );
                syncLogsHolder.prepend( '<li><a href="' + yopifyYoDashboardUrl + '">Click here</a> to go to Yo dashboard.</li>' );
            }
        }

    }

    if( typeof yopifyYoSyncOrdersUrl != "undefined" && yopifyYoSyncOrdersUrl )
    {
        $( document ).on( 'click', '#yopify_yo_start_sync', function()
        {
            $( this ).hide();
            $( '.yopify-yo-sync-orders-container' ).show();
            syncLogsHolder.prepend( '<li>Counting orders...</li>' );
            $.getJSON( ajaxurl + '?action=yopify_yo_count_orders', function( response )
            {
                response = $.parseJSON( JSON.stringify( response ) );

                if( response && response.status == '1' )
                {
                    var totalOrders = response.count;

                    if( totalOrders > 0 )
                    {
                        syncLogsHolder.prepend( '<li>' + totalOrders + ' order(s) found. Sync starting...</li>' );
                        syncLogsHolder.prepend( '<li>Sync in progress</li>' );

                        totalOrders = totalOrders > 35 ? 35 : totalOrders;
                        $( '#totalOrdersCount' ).text( totalOrders );

                        yopifyYoSyncOrders( 1, totalOrders );
                    }
                    else
                    {
                        syncLogsHolder.prepend( '<li>No order was found.</li>' );
                        $( '#totalOrdersCount' ).text( totalOrders );
                        $yopifyYoProgressBar.css( {
                            width: '100%'
                        } );
                        syncLogsHolder.prepend( '<li><a href="' + yopifyYoDashboardUrl + '">Click here</a> to go to Yo dashboard.</li>' );
                    }

                }
                else
                {
                    syncLogsHolder.prepend( '<li class="error">' + (typeof response.error != "undefined" ? response.error : "Unknown error") + '</li>' );
                    syncLogsHolder.prepend( '<li><a href="' + yopifyYoDashboardUrl + '">Click here</a> to go to Yo dashboard.</li>' );
                }
            } );
        } );

    }

})( jQuery );

