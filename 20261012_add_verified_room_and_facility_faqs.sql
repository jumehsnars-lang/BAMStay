SET NAMES utf8mb4;

USE bamstay;

INSERT IGNORE INTO public_faqs (category, question, answer, is_approved, is_active, sort_order)
VALUES
  (
    'pricing',
    'What is the monthly rate for a room?',
    '₱1,800 per month ang rate sa Bistro Bam Dormitories para sa bawat Room 1–5.',
    1,
    1,
    15
  ),
  (
    'availability',
    'Which bedspaces are listed as available in Room 1?',
    'Sa Room 1, available ang Beds B, D, at F. Occupied ang Beds A, C, at E.',
    1,
    1,
    31
  ),
  (
    'availability',
    'Which bedspaces are listed as available in Room 2?',
    'Sa Room 2, available ang Beds B, C, at D. Occupied ang Bed A.',
    1,
    1,
    32
  ),
  (
    'availability',
    'Which bedspaces are listed as available in Room 3?',
    'Sa Room 3, available ang Beds D, F, G, at H. Occupied ang Beds A, B, C, at E.',
    1,
    1,
    33
  ),
  (
    'availability',
    'Which bedspaces are listed as available in Room 4?',
    'Sa Room 4, available ang Beds B at D. Occupied ang Beds A at C.',
    1,
    1,
    34
  ),
  (
    'availability',
    'Which bedspaces are listed as available in Room 5?',
    'Sa Room 5, available ang Bed D. Occupied ang Beds A, B, at C.',
    1,
    1,
    35
  ),
  (
    'room-details',
    'What are the details and listed bedspaces for Room 1?',
    'Ang Room 1 ay bright at cozy na bunk-bed room sa tabi ng balcony. May ceiling fan, storage space, electrical outlet, at natural lighting. May 6 bedspaces: available ang Beds B, D, at F; occupied ang Beds A, C, at E. Hindi reservation ang inquiry.',
    1,
    1,
    21
  ),
  (
    'room-details',
    'What are the details and listed bedspaces for Room 2?',
    'Ang Room 2 ay spacious at bright na kuwarto sa tabi ng bintana at balcony. May ceiling fan, study table, electrical outlet, at natural lighting. May 4 bedspaces: available ang Beds B, C, at D; occupied ang Bed A. Hindi reservation ang inquiry.',
    1,
    1,
    22
  ),
  (
    'room-details',
    'What are the details and listed bedspaces for Room 3?',
    'Ang Room 3 ay cozy at comfortable na kuwarto malapit sa Shared Kitchen at Living Room. May ceiling fan, storage space, at study table. May 8 bedspaces: available ang Beds D, F, G, at H; occupied ang Beds A, B, C, at E. Hindi reservation ang inquiry.',
    1,
    1,
    23
  ),
  (
    'room-details',
    'What are the details and listed bedspaces for Room 4?',
    'Ang Room 4 ay cozy at minimalist na space para sa simple, comfortable, at relaxing na stay. May 4 bedspaces: available ang Beds B at D; occupied ang Beds A at C. Hindi reservation ang inquiry.',
    1,
    1,
    24
  ),
  (
    'room-details',
    'What are the details and listed bedspaces for Room 5?',
    'Ang Room 5 ay cozy at convenient na kuwarto malapit sa Shared Kitchen. May 4 bedspaces: available ang Bed D; occupied ang Beds A, B, at C. Temporary Room 1 photo ang ginagamit para sa Room 5. Hindi reservation ang inquiry.',
    1,
    1,
    25
  ),
  (
    'facilities',
    'Is electricity included in the monthly room rate?',
    'Unlimited Electricity ang facility sa Bistro Bam Dormitories, at kasama sa monthly rate ang paggamit ng kuryente.',
    1,
    1,
    41
  ),
  (
    'facilities',
    'What does BAMStay provide for water, laundry, and cooking?',
    'Bistro Bam Dormitories provides 24/7 Water, a shared Laundry Area for washing and drying clothes, and a Shared Kitchen where residents can prepare their own meals.',
    1,
    1,
    42
  ),
  (
    'facilities',
    'What security facility is listed at BAMStay?',
    'Bistro Bam Dormitories has CCTV Security in common areas.',
    1,
    1,
    43
  );

UPDATE public_faqs
SET answer = '₱1,800 per month ang rate sa Bistro Bam Dormitories para sa bawat Room 1–5.'
WHERE question = 'What is the monthly rate for a room?'
  AND answer <> '₱1,800 per month ang rate sa Bistro Bam Dormitories para sa bawat Room 1–5.';

UPDATE public_faqs
SET answer = CASE question
  WHEN 'Which bedspaces are listed as available in Room 1?' THEN 'Sa Room 1, available ang Beds B, D, at F. Occupied ang Beds A, C, at E.'
  WHEN 'Which bedspaces are listed as available in Room 2?' THEN 'Sa Room 2, available ang Beds B, C, at D. Occupied ang Bed A.'
  WHEN 'Which bedspaces are listed as available in Room 3?' THEN 'Sa Room 3, available ang Beds D, F, G, at H. Occupied ang Beds A, B, C, at E.'
  WHEN 'Which bedspaces are listed as available in Room 4?' THEN 'Sa Room 4, available ang Beds B at D. Occupied ang Beds A at C.'
  WHEN 'Which bedspaces are listed as available in Room 5?' THEN 'Sa Room 5, available ang Bed D. Occupied ang Beds A, B, at C.'
END
WHERE question IN (
  'Which bedspaces are listed as available in Room 1?',
  'Which bedspaces are listed as available in Room 2?',
  'Which bedspaces are listed as available in Room 3?',
  'Which bedspaces are listed as available in Room 4?',
  'Which bedspaces are listed as available in Room 5?'
);

UPDATE public_faqs
SET answer = 'Makikita sa Bistro Bam Dormitories ang mga bedspace na available, pero hindi nakakapag-reserve ng kama ang chatbot o ang inquiry form. Para mag-request ng bedspace, gamitin ang inquiry form.'
WHERE question = 'Can the chatbot confirm a bedspace is currently available or reserve it?';

UPDATE public_faqs
SET answer = 'Unlimited Electricity ang facility sa Bistro Bam Dormitories, at kasama sa monthly rate ang paggamit ng kuryente.'
WHERE question = 'Is electricity included in the monthly room rate?'
  AND answer <> 'Unlimited Electricity ang facility sa Bistro Bam Dormitories, at kasama sa monthly rate ang paggamit ng kuryente.';

UPDATE public_faqs
SET answer = REPLACE(
  REPLACE(answer, 'These are website-listed statuses, not live-confirmed availability. Ask the administrator through the inquiry form to confirm; an inquiry does not reserve a bed.', ''),
  'These bed statuses are website-listed, not live-confirmed availability. Ask the administrator through the inquiry form to confirm; an inquiry does not reserve a bed.',
  ''
)
WHERE category = 'room-details';

UPDATE public_faqs
SET answer = CASE question
  WHEN 'What are the details and listed bedspaces for Room 1?' THEN 'Ang Room 1 ay bright at cozy na bunk-bed room sa tabi ng balcony. May ceiling fan, storage space, electrical outlet, at natural lighting. May 6 bedspaces: available ang Beds B, D, at F; occupied ang Beds A, C, at E.'
  WHEN 'What are the details and listed bedspaces for Room 2?' THEN 'Ang Room 2 ay spacious at bright na kuwarto sa tabi ng bintana at balcony. May ceiling fan, study table, electrical outlet, at natural lighting. May 4 bedspaces: available ang Beds B, C, at D; occupied ang Bed A.'
  WHEN 'What are the details and listed bedspaces for Room 3?' THEN 'Ang Room 3 ay cozy at comfortable na kuwarto malapit sa Shared Kitchen at Living Room. May ceiling fan, storage space, at study table. May 8 bedspaces: available ang Beds D, F, G, at H; occupied ang Beds A, B, C, at E.'
  WHEN 'What are the details and listed bedspaces for Room 4?' THEN 'Ang Room 4 ay cozy at minimalist na space para sa simple, comfortable, at relaxing na stay. May 4 bedspaces: available ang Beds B at D; occupied ang Beds A at C.'
  WHEN 'What are the details and listed bedspaces for Room 5?' THEN 'Ang Room 5 ay cozy at convenient na kuwarto malapit sa Shared Kitchen. May 4 bedspaces: available ang Bed D; occupied ang Beds A, B, at C. Temporary Room 1 photo ang ginagamit para sa Room 5.'
END
WHERE question IN (
  'What are the details and listed bedspaces for Room 1?',
  'What are the details and listed bedspaces for Room 2?',
  'What are the details and listed bedspaces for Room 3?',
  'What are the details and listed bedspaces for Room 4?',
  'What are the details and listed bedspaces for Room 5?'
);

UPDATE public_faqs
SET question = 'What facilities does Bistro Bam Dormitories offer?'
WHERE question = 'What facilities are listed on the website?';

UPDATE public_faqs
SET answer = CASE question
  WHEN 'What is the monthly rate for a room?' THEN '₱1,800 per month ang rate sa Bistro Bam Dormitories para sa bawat Room 1-5.'
  WHEN 'What rooms are listed?' THEN 'Bistro Bam Dormitories has five rooms: Room 1 with 6 bedspaces, Room 2 with 4, Room 3 with 8, Room 4 with 4, and Room 5 with 4. See the Rooms section for descriptions and bedspace details.'
  WHEN 'What facilities does Bistro Bam Dormitories offer?' THEN 'Bistro Bam Dormitories offers Free WiFi, Unlimited Electricity, 24/7 Water, a Laundry Area, a Shared Kitchen, and CCTV Security in common areas.'
  WHEN 'Where is BAMStay located?' THEN 'Bistro Bam Dormitories is located at Bucana Elementary School, 3J8G+3GF, Nasugbu, Batangas. Use the Get Directions link in the Location section for a map.'
  WHEN 'What places are near BAMStay?' THEN 'Nearby landmarks for Bistro Bam Dormitories include Batangas State University - ARASOF Nasugbu, Nasugbu Public Market, public transport terminals, fast-food restaurants, and churches.'
  WHEN 'What does BAMStay provide for water, laundry, and cooking?' THEN 'Bistro Bam Dormitories provides 24/7 Water, a shared Laundry Area for washing and drying clothes, and a Shared Kitchen where residents can prepare their own meals.'
  WHEN 'What security facility is listed at BAMStay?' THEN 'Bistro Bam Dormitories has CCTV Security in common areas.'
  WHEN 'How can I ask about a room?' THEN 'To inquire about a room at Bistro Bam Dormitories, use the Inquire Now button or the inquiry form. You can select a room and an available bedspace preference. Submitting an inquiry does not reserve a bed.'
  ELSE answer
END
WHERE question IN (
  'What is the monthly rate for a room?',
  'What rooms are listed?',
  'What facilities does Bistro Bam Dormitories offer?',
  'Where is BAMStay located?',
  'What places are near BAMStay?',
  'What does BAMStay provide for water, laundry, and cooking?',
  'What security facility is listed at BAMStay?',
  'How can I ask about a room?'
);

UPDATE public_faqs
SET answer = REPLACE(answer, 'BAMStay', 'Bistro Bam Dormitories')
WHERE question = 'What is BAMStay?';

UPDATE public_faqs
SET answer = 'Makikita sa Bistro Bam Dormitories ang mga bedspace na available, pero hindi nakakapag-reserve ng kama ang chatbot o ang inquiry form. Para mag-request ng bedspace, gamitin ang inquiry form.'
WHERE question = 'Can the chatbot confirm a bedspace is currently available or reserve it?';
