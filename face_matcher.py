import cv2
import sys
import json
import os
import glob

def find_matching_faces(uploaded_image_path, album_folder):
    try:
        if not os.path.exists(uploaded_image_path):
            return json.dumps({"error": "Uploaded image not found"})
        
        if not os.path.exists(album_folder):
            return json.dumps({"error": "Album folder not found"})
        
        # Load Haar Cascade for face detection
        cascade_path = cv2.data.haarcascades + 'haarcascade_frontalface_default.xml'
        face_cascade = cv2.CascadeClassifier(cascade_path)
        
        # Read uploaded image
        uploaded_img = cv2.imread(uploaded_image_path)
        if uploaded_img is None:
            return json.dumps({"error": "Could not read uploaded image"})
        
        # Convert to grayscale
        gray_uploaded = cv2.cvtColor(uploaded_img, cv2.COLOR_BGR2GRAY)
        
        # Detect faces in uploaded image
        uploaded_faces = face_cascade.detectMultiScale(
            gray_uploaded,
            scaleFactor=1.1,
            minNeighbors=5,
            minSize=(30, 30)
        )
        
        if len(uploaded_faces) == 0:
            return json.dumps({"error": "No face detected in uploaded image. Please use a clear photo."})
        
        # Get the largest face
        (x1, y1, w1, h1) = max(uploaded_faces, key=lambda face: face[2] * face[3])
        uploaded_face = uploaded_img[y1:y1+h1, x1:x1+w1]
        uploaded_face_resized = cv2.resize(uploaded_face, (100, 100))
        uploaded_face_gray = cv2.cvtColor(uploaded_face_resized, cv2.COLOR_BGR2GRAY)
        
        matching_images = []
        
        # Get all image files
        image_files = []
        for ext in ['jpg', 'jpeg', 'png', 'gif', 'JPG', 'JPEG', 'PNG', 'GIF']:
            image_files.extend(glob.glob(os.path.join(album_folder, f"*.{ext}")))
        
        if len(image_files) == 0:
            return json.dumps({"error": "No images found in album"})
        
        # Compare with each album image
        for image_path in image_files:
            try:
                album_img = cv2.imread(image_path)
                if album_img is None:
                    continue
                
                gray_album = cv2.cvtColor(album_img, cv2.COLOR_BGR2GRAY)
                album_faces = face_cascade.detectMultiScale(
                    gray_album,
                    scaleFactor=1.1,
                    minNeighbors=5,
                    minSize=(30, 30)
                )
                
                # Check each face in album image
                for (x2, y2, w2, h2) in album_faces:
                    album_face = album_img[y2:y2+h2, x2:x2+w2]
                    album_face_resized = cv2.resize(album_face, (100, 100))
                    album_face_gray = cv2.cvtColor(album_face_resized, cv2.COLOR_BGR2GRAY)
                    
                    # Template matching
                    result = cv2.matchTemplate(
                        album_face_gray,
                        uploaded_face_gray,
                        cv2.TM_CCOEFF_NORMED
                    )
                    
                    similarity = result[0][0]
                    
                    # Match threshold (0.5-0.7 works well)
                    if similarity > 0.55:
                        matching_images.append(os.path.basename(image_path))
                        break
                        
            except Exception:
                continue
        
        # Remove duplicates
        matching_images = list(set(matching_images))
        
        if matching_images:
            return json.dumps({"matches": matching_images})
        else:
            return json.dumps({"error": "No match found"})
            
    except Exception as e:
        return json.dumps({"error": "Processing error: " + str(e)})

if __name__ == "__main__":
    if len(sys.argv) < 3:
        print(json.dumps({"error": "Missing arguments"}))
        sys.exit(1)
    
    result = find_matching_faces(sys.argv[1], sys.argv[2])
    print(result)
