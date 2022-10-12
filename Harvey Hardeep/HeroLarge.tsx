import React, { useState } from "react";
import styled from "styled-components";
import {
  Page_Pagecontent_Modules_Hero,
  Post_Pagecontent_Modules_Hero,
} from "client";
import Text, { SimpleMarkdownFormat } from "../styles/Text";
import Button from "./Button";
import VideoModal from "./VideoModal";

interface HeroProps {
  data: Page_Pagecontent_Modules_Hero | Post_Pagecontent_Modules_Hero;
}

export const prepassHeroLargeFields = ({ data }: HeroProps) => {
  // this is purely used to workaround issue with GQty
  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  const heroObject = {
    fieldGroupName: data?.fieldGroupName,
    __typename: data?.__typename,
    heroVisible: data?.heroVisible,
    heroStyle: data?.heroStyle,
    heroImage: {
      __typename: data?.heroImage?.__typename,
      id: data?.heroImage?.id,
      mediaItemUrl: data?.heroImage?.mediaItemUrl,
      altText: data?.heroImage?.altText,
    },
    heroTitle: data?.heroTitle,
    heroDescription: data?.heroDescription,
    heroCtaurl: data?.heroCtaurl,
    heroCtaVideoUrl: data?.heroCtaVideoUrl,
    heroCtalabel: data?.heroCtalabel,
  };
};

const StyledOuterContainer = styled.div<{ mediaSrc?: string }>`
  background-image: url("${({ mediaSrc }) => mediaSrc}");
  background-position: center;
  background-size: cover;
  background-color: ${({ theme }) => theme.colors?.black};
`;

const StyledContainer = styled.div`
  ${({ theme }) => theme.helpers?.LayoutContainer};
  ${({ theme }) => theme.helpers?.GridContainer};
  grid-template-columns: repeat(2, 1fr);

  > div:nth-of-type(1) {
    grid-area: 1 / 1 / 2 / 3;
    min-height: 608px;
    padding-top: 50px;
    padding-bottom: 50px;
    display: flex;
    flex-direction: column;
    flex-wrap: nowrap;
    justify-content: center;
    align-content: stretch;
    align-items: center;
    text-align: center;
  }

  @media ${({ theme }) => theme.layouts?.above_medium} {
    grid-template-columns: repeat(12, 1fr);

    > div:nth-of-type(1) {
      grid-area: 1 / 4 / 2 / 10;
      min-height: 682px;
    }
  }
`;

const StyledTextContainer = styled(Text)`
  padding-top: 16px;
`;

const StyledButtonContainer = styled(Text)`
  padding-top: 32px;
  padding-bottom: 0;

  @media ${({ theme }) => theme.layouts?.above_medium} {
    padding-top: 32px;
  }
`;

export default function Hero({ data }: HeroProps) {
  const [modalVisible, setModalVisible] = useState(false);

  const openModal = () => {
    setModalVisible(true);
  };

  const closeModal = () => {
    setModalVisible(false);
  };

  return (
    <>
      <StyledOuterContainer mediaSrc={data?.heroImage?.mediaItemUrl}>
        <StyledContainer>
          <div>
            <Text
              as="h1"
              size="headingxlarge"
              fontWeight="bold"
              textTheme="dark"
            >
              <SimpleMarkdownFormat>{data?.heroTitle}</SimpleMarkdownFormat>
            </Text>
            {data?.heroDescription && (
              <StyledTextContainer
                forwardedAs="p"
                size="textxlarge"
                fontWeight="regular"
                textTheme="dark"
              >
                <SimpleMarkdownFormat>
                  {data?.heroDescription}
                </SimpleMarkdownFormat>
              </StyledTextContainer>
            )}
            {data?.heroCtaurl && (
              <StyledButtonContainer forwardedAs="p" size="textbutton1">
                {data?.heroCtaVideoUrl ? (
                  <Button
                    link={data?.heroCtaurl}
                    linkTitle={data?.heroCtalabel}
                    buttonStyle="primary"
                    onClick={openModal}
                    asButton={true}
                  />
                ) : (
                  <Button
                    link={data?.heroCtaurl}
                    linkTitle={data?.heroCtalabel}
                    buttonStyle="primary"
                  />
                )}
              </StyledButtonContainer>
            )}
          </div>
        </StyledContainer>
      </StyledOuterContainer>
      {data?.heroCtaVideoUrl && modalVisible && (
        <VideoModal
          visible={modalVisible}
          media={data?.heroCtaVideoUrl}
          closeModal={closeModal}
        />
      )}
    </>
  );
}
