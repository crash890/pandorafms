package pandroid_event_viewer.pandorafms;

import java.util.ArrayList;
import java.util.Calendar;
import java.util.List;

import org.apache.http.HttpEntity;
import org.apache.http.HttpResponse;
import org.apache.http.NameValuePair;
import org.apache.http.client.entity.UrlEncodedFormEntity;
import org.apache.http.client.methods.HttpPost;
import org.apache.http.impl.client.DefaultHttpClient;
import org.apache.http.message.BasicNameValuePair;

import android.app.Activity;
import android.app.Notification;
import android.app.NotificationManager;
import android.app.PendingIntent;
import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.Intent;
import android.content.SharedPreferences;
import android.util.Log;

public class AlarmReceiver extends BroadcastReceiver {
	public String url;
	public String user;
	public String password;
	
	public long count_events;
	public int more_criticity;

	@Override
	public void onReceive(Context context, Intent intent) {
		Log.e("AlarmReceiver", "onReceive");
		checkNewEvents(context);
	}
	
	public void checkNewEvents(Context context) {
		if (this.url == null) {
	        SharedPreferences preferences = context.getSharedPreferences(
	        	context.getString(R.string.const_string_preferences), 
	        	Activity.MODE_PRIVATE);
	            
	        this.url = preferences.getString("url", "");
	        this.user = preferences.getString("user", "");
	        this.password = preferences.getString("password", "");
	        Calendar c = Calendar.getInstance();
	    	long now = (c.getTimeInMillis() / 1000);
	        long old_previous_filterTimestamp = preferences.getLong("previous_filterTimestamp", now);
	        
	        if ((user.length() == 0) && (password.length() == 0)
	            	&& (url.length() == 0)) {
	        	return;
	        }
	        
	    	try {
	            DefaultHttpClient httpClient = new DefaultHttpClient();
	            UrlEncodedFormEntity entity;
	            HttpPost httpPost;
	            List<NameValuePair> parameters;
	            HttpResponse response;
	            HttpEntity entityResponse;
	            String return_api;
	    		
		    	httpPost = new HttpPost(this.url + "/include/api.php");
		    	
		    	String parametersAPI = serializeParams2Api(context);
		    	
		    	//Get total count.
		    	parameters = new ArrayList<NameValuePair>();
		    	parameters.add(new BasicNameValuePair("user", this.user));
		    	parameters.add(new BasicNameValuePair("pass", this.password));
		    	parameters.add(new BasicNameValuePair("op", "get"));
		    	parameters.add(new BasicNameValuePair("op2", "events"));
		    	parameters.add(new BasicNameValuePair("other_mode", "url_encode_separator_|"));
		    	parameters.add(new BasicNameValuePair("return_type", "csv"));
		    	parameters.add(new BasicNameValuePair("other", parametersAPI + "|total"));
		    	entity = new UrlEncodedFormEntity(parameters);
		    	httpPost.setEntity(entity);
		    	response = httpClient.execute(httpPost);
		    	entityResponse = response.getEntity();
		    	return_api = Core.convertStreamToString(entityResponse.getContent());
		    	
		    	return_api = return_api.replace("\n", "");
		    	Log.e("AlarmReceiver checkNewEvents", "" + return_api);
		    	this.count_events = new Long(return_api).longValue();
		    	
		    	//Check the event more critical
		    	if (this.count_events != 0) {
			    	parameters = new ArrayList<NameValuePair>();
			    	parameters.add(new BasicNameValuePair("user", this.user));
			    	parameters.add(new BasicNameValuePair("pass", this.password));
			    	parameters.add(new BasicNameValuePair("op", "get"));
			    	parameters.add(new BasicNameValuePair("op2", "events"));
			    	parameters.add(new BasicNameValuePair("other_mode", "url_encode_separator_|"));
			    	parameters.add(new BasicNameValuePair("return_type", "csv"));
			    	parameters.add(new BasicNameValuePair("other", parametersAPI + "|more_criticity"));
			    	entity = new UrlEncodedFormEntity(parameters);
			    	httpPost.setEntity(entity);
			    	response = httpClient.execute(httpPost);
			    	entityResponse = response.getEntity();
			    	return_api = Core.convertStreamToString(entityResponse.getContent());
			    	return_api = return_api.replace("\n", "");
			    	this.more_criticity = new Integer(return_api).intValue();
			    	
			    	notificationEvent(context);
		    	}
		    	else {
		    		this.more_criticity = -1;
		    		
		    		//Restore timestamp
		    		SharedPreferences.Editor editorPreferences = preferences.edit();
		        	editorPreferences.putLong("previous_filterTimestamp", old_previous_filterTimestamp);
		        	editorPreferences.commit();
		    	}
		    	
	    	}
	    	catch (Exception e) {
	    		Log.e("EXCEPTION checkNewEvents", e.getMessage());
	    		
	    		return;
	    	}
		}
	    	
	}
	
    public String serializeParams2Api(Context context) {
        SharedPreferences preferences = context.getSharedPreferences(
        	 context.getString(R.string.const_string_preferences), 
        	Activity.MODE_PRIVATE);
        
    	String filterAgentName = preferences.getString("filterAgentName", "");
    	int filterIDGroup = preferences.getInt("filterIDGroup", 0);
    	int filterSeverity = preferences.getInt("filterSeverity", -1);
    	int filterStatus = preferences.getInt("filterStatus", 3);
    	String filterEventSearch = preferences.getString("filterEventSearch", "");
    	
    	
    	Calendar c = Calendar.getInstance();
    	long now = (c.getTimeInMillis() / 1000);
    	long filterTimestamp = preferences.getLong("filterTimestamp", now);
    	SharedPreferences.Editor editorPreferences = preferences.edit();
    	editorPreferences.putLong("filterTimestamp", now); //Save for the next execution.
    	editorPreferences.putLong("previous_filterTimestamp", filterTimestamp); //Save and the previous for the list.
    	if (editorPreferences.commit()) {
    		Log.e("AlarmReceiver serializeParams2Api", "YES COMMIT");
    	}
    	else {
    		Log.e("AlarmReceiver serializeParams2Api", "NOT COMMIT");
    	}
    	
    	
    	String return_var = "";
    	
    	
    	
    	return_var += ';'; //Separator for the csv
    	return_var += "|";
    	return_var += Integer.toString(filterSeverity); //Criticity or severity
    	return_var += "|";
    	return_var += filterAgentName; //The agent name
    	return_var += "|";
    	return_var += ""; //Name of module
    	return_var += "|";
    	return_var += ""; //Name of alert template
    	return_var += "|";
    	return_var += ""; //Id user
    	return_var += "|";
    	return_var += Long.toString(filterTimestamp); //The minimun timestamp
    	return_var += "|";
    	return_var += ""; //The maximum timestamp
    	return_var += "|";
    	return_var += filterStatus; //The status
    	return_var += "|";
    	return_var += filterEventSearch; //The free search in the text event description.
    	return_var += "|";
    	return_var += Integer.toString(0); //The pagination of list events
    	return_var += "|";
    	return_var += Long.toString(0); //The offset of list events
    	
    	Log.e("AlarmReceiver serializeParams2Api", return_var);
    	
    	return return_var;
    }

    private static final int NOTIFICATION_PANDROID_EVENT_VIEWER = 666;
    public void notificationEvent(Context context) {
    	String ns = Context.NOTIFICATION_SERVICE;
    	NotificationManager mNotificationManager = (NotificationManager) context.getSystemService(ns);
    	
    	
    	
    	mNotificationManager.cancel(NOTIFICATION_PANDROID_EVENT_VIEWER);
    	
    	
    	int icon;
    	CharSequence tickerText;
    	
    	switch (this.more_criticity) {
    		case 0:
    			icon = R.drawable.criticity_0;
    			tickerText = context.getString(R.string.notification_criticity_0_str)
    				.replace("%s", new Long(this.count_events).toString());
    			break;
    		case 1:
    			icon = R.drawable.criticity_1;
    			tickerText = context.getString(R.string.notification_criticity_1_str)
    				.replace("%s", new Long(this.count_events).toString());
    			break;
    		case 2:
    			icon = R.drawable.criticity_2;
    			tickerText = context.getString(R.string.notification_criticity_2_str)
    				.replace("%s", new Long(this.count_events).toString());
    			break;
    		case 3:
    			icon = R.drawable.criticity_3;
    			tickerText = context.getString(R.string.notification_criticity_3_str)
    				.replace("%s", new Long(this.count_events).toString());
    			break;
    		case 4:
    			icon = R.drawable.criticity_4;
    			tickerText = context.getString(R.string.notification_criticity_4_str)
    				.replace("%s", new Long(this.count_events).toString());
    			break;
    		default:
    			icon = R.drawable.criticity_default;
    			tickerText = context.getString(R.string.notification_criticity_2_str)
    				.replace("%s", new Long(this.count_events).toString());
    			break;
    	}
    	
    	
    	long when = System.currentTimeMillis();

    	Notification notification = new Notification(icon, tickerText, when);
    	
    	
    	notification.defaults |= Notification.DEFAULT_ALL;
    	
    	
    	notification.flags |= Notification.FLAG_AUTO_CANCEL;
    	
    	
    	Intent notificationIntent = new Intent(context, PandroidEventviewerActivity.class);
    	notificationIntent.addFlags(Intent.FLAG_ACTIVITY_CLEAR_TOP);
    	notificationIntent.addFlags(Intent.FLAG_ACTIVITY_SINGLE_TOP);
    	notificationIntent.putExtra("count_events", this.count_events);
    	notificationIntent.putExtra("more_criticity", this.more_criticity);
    	
    	PendingIntent contentIntent = PendingIntent.getActivity(context, 0, notificationIntent, 0);
    	
    	CharSequence title = context.getString(R.string.pandroid_event_viewer_str);

    	notification.setLatestEventInfo(context, title, tickerText, contentIntent);
    	
    	
    	
    	
    	mNotificationManager.notify(NOTIFICATION_PANDROID_EVENT_VIEWER, notification);
    }
}