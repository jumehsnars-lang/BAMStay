USE bamstay;

CREATE TABLE IF NOT EXISTS public_faqs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  category VARCHAR(40) NOT NULL,
  question VARCHAR(500) NOT NULL,
  answer TEXT NOT NULL,
  is_approved TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY public_faqs_question_unique (question),
  INDEX public_faqs_public_idx (is_approved, is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO public_faqs (category, question, answer, is_approved, is_active, sort_order)
VALUES
  (
    'about',
    'What is BAMStay?',
    'Bistro Bam Dormitories provides a welcoming living environment for female students and boarders, with accommodation and shared facilities.',
    1,
    1,
    10
  ),
  (
    'rooms',
    'What rooms are listed?',
    'Bistro Bam Dormitories has five rooms: Room 1 with 6 bedspaces, Room 2 with 4, Room 3 with 8, Room 4 with 4, and Room 5 with 4. See the Rooms section for descriptions and bedspace details.',
    1,
    1,
    20
  ),
  (
    'availability',
    'Can the chatbot confirm a bedspace is currently available or reserve it?',
    'The listed available bedspaces are shown in the room details. The chatbot cannot reserve a bed. Use the inquiry form to request a bedspace; an inquiry is not a reservation.',
    1,
    1,
    30
  ),
  (
    'facilities',
    'What facilities does Bistro Bam Dormitories offer?',
    'Bistro Bam Dormitories offers Free WiFi, Unlimited Electricity, 24/7 Water, a Laundry Area, a Shared Kitchen, and CCTV Security in common areas.',
    1,
    1,
    40
  ),
  (
    'location',
    'Where is BAMStay located?',
    'Bistro Bam Dormitories is located at Bucana Elementary School, 3J8G+3GF, Nasugbu, Batangas. Use the Get Directions link in the Location section for a map.',
    1,
    1,
    50
  ),
  (
    'location',
    'What places are near BAMStay?',
    'Nearby landmarks for Bistro Bam Dormitories include Batangas State University - ARASOF Nasugbu, Nasugbu Public Market, public transport terminals, fast-food restaurants, and churches.',
    1,
    1,
    60
  ),
  (
    'inquiries',
    'How can I ask about a room?',
    'To inquire about a room at Bistro Bam Dormitories, use the Inquire Now button or the inquiry form. You can select a room and an available bedspace preference. Submitting an inquiry does not reserve a bed.',
    1,
    1,
    70
  );
