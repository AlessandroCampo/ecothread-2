package com.ecothread.app;

import android.content.ActivityNotFoundException;
import android.content.Intent;
import android.net.Uri;

import com.getcapacitor.Plugin;
import com.getcapacitor.PluginCall;
import com.getcapacitor.PluginMethod;
import com.getcapacitor.annotation.CapacitorPlugin;

@CapacitorPlugin(name = "OpenExternal")
public class OpenExternalPlugin extends Plugin {

    @PluginMethod()
    public void open(PluginCall call) {
        String url = call.getString("url");
        if (url == null) {
            call.reject("URL is required");
            return;
        }

        try {
            Intent intent = new Intent(Intent.ACTION_VIEW, Uri.parse(url));
            intent.setPackage("app.phantom");
            getActivity().startActivity(intent);
            call.resolve();
        } catch (ActivityNotFoundException e) {
            try {
                Intent fallback = new Intent(Intent.ACTION_VIEW, Uri.parse(url));
                getActivity().startActivity(fallback);
                call.resolve();
            } catch (Exception ex) {
                call.reject("Failed to open URL: " + ex.getMessage());
            }
        }
    }
}
