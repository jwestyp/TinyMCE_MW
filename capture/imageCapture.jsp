<%@ page import="java.io.*"%><%
	String image = request.getParameter("image");
	
	// size protection 
	if(image==null || image.length()>100000) return;
	
	byte[] bytes = org.apache.commons.codec.binary.Base64.decodeBase64(image);
	if(bytes==null) return;
		
	String save = request.getParameter("save");
	String name = request.getParameter("name");
	String type = request.getParameter("type");
	if(save!=null && name!=null && ("JPG".equalsIgnoreCase(type) || "PNG".equalsIgnoreCase(type) )){
		String webappRoot = getServletContext().getRealPath("/");
		File folder = new File(webappRoot + "/images/math/");
		File fileName = new File(folder, name + "." + type);
		FileOutputStream fos = new FileOutputStream(fileName);
		fos.write(bytes);
		fos.close();
		/*
		 the path can be:
		 	http://your_server/.../img/..
		 or
		 	/capture/img/...
		 or relative
		 	img/...
		*/
		
		%>img/<%=name%>.<%=type%><%
	}else{
		response.setContentType("image/jpeg");
		OutputStream os = response.getOutputStream();
		for(int i=0; i<bytes.length; i++){
			os.write(bytes[i]);
		}
		os.flush();
		os.close();
	}
		
%>
